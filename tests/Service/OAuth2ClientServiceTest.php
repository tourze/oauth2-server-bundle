<?php

namespace Tourze\OAuth2ServerBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Repository\OAuth2ClientRepository;
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;

/**
 * OAuth2ClientService单元测试
 */
class OAuth2ClientServiceTest extends TestCase
{
    private OAuth2ClientRepository&MockObject $mockRepository;
    private UserInterface&MockObject $mockUser;
    private OAuth2ClientService $clientService;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(OAuth2ClientRepository::class);
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->method('getUserIdentifier')->willReturn('test@example.com');
        
        $this->clientService = new OAuth2ClientService($this->mockRepository);
    }

    public function test_createClient_withAllParameters(): void
    {
        $name = 'Test Application';
        $redirectUris = ['https://example.com/callback'];
        $grantTypes = ['authorization_code', 'refresh_token'];
        $description = 'Test application description';
        $scopes = ['read', 'write'];

        $this->mockRepository->expects($this->once())
            ->method('findByClientId')
            ->willReturn(null); // 确保clientId唯一
        
        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(OAuth2Client::class));

        $client = $this->clientService->createClient(
            $this->mockUser,
            $name,
            $redirectUris,
            $grantTypes,
            $description,
            true,
            $scopes
        );

        $this->assertInstanceOf(OAuth2Client::class, $client);
        $this->assertSame($this->mockUser, $client->getUser());
        $this->assertSame($name, $client->getName());
        $this->assertSame($redirectUris, $client->getRedirectUris());
        $this->assertSame($grantTypes, $client->getGrantTypes());
        $this->assertSame($description, $client->getDescription());
        $this->assertTrue($client->isConfidential());
        $this->assertSame($scopes, $client->getScopes());
        $this->assertNotEmpty($client->getClientId());
        $this->assertNotEmpty($client->getClientSecret());
        $this->assertTrue(str_starts_with($client->getClientId(), 'client_'));
    }

    public function test_createClient_withMinimalParameters(): void
    {
        $name = 'Simple Client';

        $this->mockRepository->method('findByClientId')->willReturn(null);
        $this->mockRepository->expects($this->once())->method('save');

        $client = $this->clientService->createClient($this->mockUser, $name);

        $this->assertSame($name, $client->getName());
        $this->assertSame([], $client->getRedirectUris());
        $this->assertSame(['client_credentials'], $client->getGrantTypes());
        $this->assertNull($client->getDescription());
        $this->assertTrue($client->isConfidential());
        $this->assertNull($client->getScopes());
    }

    public function test_createClient_generatesUniqueClientId(): void
    {
        // 第一次查询返回已存在的客户端，第二次返回null
        $this->mockRepository->expects($this->exactly(2))
            ->method('findByClientId')
            ->willReturnOnConsecutiveCalls(
                new OAuth2Client(), // 第一次返回已存在的客户端
                null // 第二次返回null，表示ID唯一
            );
        
        $this->mockRepository->expects($this->once())->method('save');

        $client = $this->clientService->createClient($this->mockUser, 'Test Client');
        
        $this->assertNotEmpty($client->getClientId());
    }

    public function test_validateClient_withValidConfidentialClient(): void
    {
        $clientId = 'test_client';
        $clientSecret = 'test_secret';
        $hashedSecret = password_hash($clientSecret, PASSWORD_BCRYPT);
        
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('isConfidential')->willReturn(true);
        $mockClient->method('getClientSecret')->willReturn($hashedSecret);

        $this->mockRepository->expects($this->once())
            ->method('findByClientId')
            ->with($clientId)
            ->willReturn($mockClient);

        $result = $this->clientService->validateClient($clientId, $clientSecret);

        $this->assertSame($mockClient, $result);
    }

    public function test_validateClient_withInvalidClientSecret(): void
    {
        $clientId = 'test_client';
        $clientSecret = 'wrong_secret';
        $hashedSecret = password_hash('correct_secret', PASSWORD_BCRYPT);
        
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('isConfidential')->willReturn(true);
        $mockClient->method('getClientSecret')->willReturn($hashedSecret);

        $this->mockRepository->method('findByClientId')->willReturn($mockClient);

        $result = $this->clientService->validateClient($clientId, $clientSecret);

        $this->assertNull($result);
    }

    public function test_validateClient_withPublicClient(): void
    {
        $clientId = 'public_client';
        
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('isConfidential')->willReturn(false);

        $this->mockRepository->method('findByClientId')->willReturn($mockClient);

        $result = $this->clientService->validateClient($clientId);

        $this->assertSame($mockClient, $result);
    }

    public function test_validateClient_withNonExistentClient(): void
    {
        $clientId = 'non_existent_client';

        $this->mockRepository->method('findByClientId')->willReturn(null);

        $result = $this->clientService->validateClient($clientId, 'any_secret');

        $this->assertNull($result);
    }

    public function test_validateClient_confidentialClientWithoutSecret(): void
    {
        $clientId = 'confidential_client';
        
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('isConfidential')->willReturn(true);

        $this->mockRepository->method('findByClientId')->willReturn($mockClient);

        $result = $this->clientService->validateClient($clientId); // 没有提供secret

        $this->assertNull($result);
    }

    public function test_verifyClientSecret_withCorrectSecret(): void
    {
        $plainSecret = 'test_secret';
        $hashedSecret = password_hash($plainSecret, PASSWORD_BCRYPT);
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('getClientSecret')->willReturn($hashedSecret);

        $result = $this->clientService->verifyClientSecret($mockClient, $plainSecret);

        $this->assertTrue($result);
    }

    public function test_verifyClientSecret_withIncorrectSecret(): void
    {
        $plainSecret = 'wrong_secret';
        $hashedSecret = password_hash('correct_secret', PASSWORD_BCRYPT);
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('getClientSecret')->willReturn($hashedSecret);

        $result = $this->clientService->verifyClientSecret($mockClient, $plainSecret);

        $this->assertFalse($result);
    }

    public function test_validateRedirectUri_withExactMatch(): void
    {
        $redirectUri = 'https://example.com/callback';
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('getRedirectUris')->willReturn([$redirectUri]);

        $result = $this->clientService->validateRedirectUri($mockClient, $redirectUri);

        $this->assertTrue($result);
    }

    public function test_validateRedirectUri_withSubpathMatch(): void
    {
        $allowedUri = 'https://example.com';
        $requestedUri = 'https://example.com/callback';
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('getRedirectUris')->willReturn([$allowedUri]);

        $result = $this->clientService->validateRedirectUri($mockClient, $requestedUri);

        $this->assertTrue($result);
    }

    public function test_validateRedirectUri_withNonMatchingUri(): void
    {
        $allowedUri = 'https://example.com/callback';
        $requestedUri = 'https://malicious.com/callback';
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('getRedirectUris')->willReturn([$allowedUri]);

        $result = $this->clientService->validateRedirectUri($mockClient, $requestedUri);

        $this->assertFalse($result);
    }

    public function test_validateRedirectUri_withEmptyAllowedUris(): void
    {
        $requestedUri = 'https://example.com/callback';
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('getRedirectUris')->willReturn([]);

        $result = $this->clientService->validateRedirectUri($mockClient, $requestedUri);

        $this->assertFalse($result);
    }

    public function test_supportsGrantType_returnsTrueForSupportedType(): void
    {
        $grantType = 'authorization_code';
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('supportsGrantType')->with($grantType)->willReturn(true);

        $result = $this->clientService->supportsGrantType($mockClient, $grantType);

        $this->assertTrue($result);
    }

    public function test_supportsGrantType_returnsFalseForUnsupportedType(): void
    {
        $grantType = 'password';
        
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->method('supportsGrantType')->with($grantType)->willReturn(false);

        $result = $this->clientService->supportsGrantType($mockClient, $grantType);

        $this->assertFalse($result);
    }

    public function test_updateClient_savesClient(): void
    {
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($mockClient);

        $this->clientService->updateClient($mockClient);
    }

    public function test_regenerateClientSecret_generatesNewSecret(): void
    {
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->expects($this->once())
            ->method('setClientSecret')
            ->with($this->isType('string'));

        $this->mockRepository->expects($this->once())->method('save');

        $newSecret = $this->clientService->regenerateClientSecret($mockClient);

        $this->assertNotEmpty($newSecret);
    }

    public function test_disableClient_setsEnabledToFalse(): void
    {
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->expects($this->once())
            ->method('setEnabled')
            ->with(false);

        $this->mockRepository->expects($this->once())->method('save');

        $this->clientService->disableClient($mockClient);
    }

    public function test_enableClient_setsEnabledToTrue(): void
    {
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);
        $mockClient->expects($this->once())
            ->method('setEnabled')
            ->with(true);

        $this->mockRepository->expects($this->once())->method('save');

        $this->clientService->enableClient($mockClient);
    }

    public function test_deleteClient_removesClient(): void
    {
        /** @var OAuth2Client&MockObject $mockClient */
        $mockClient = $this->createMock(OAuth2Client::class);

        $this->mockRepository->expects($this->once())
            ->method('remove')
            ->with($mockClient);

        $this->clientService->deleteClient($mockClient);
    }

    public function test_getClientsByUser_returnsUserClients(): void
    {
        $expectedClients = [
            $this->createMock(OAuth2Client::class),
            $this->createMock(OAuth2Client::class),
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->mockUser)
            ->willReturn($expectedClients);

        $result = $this->clientService->getClientsByUser($this->mockUser);

        $this->assertSame($expectedClients, $result);
    }
} 