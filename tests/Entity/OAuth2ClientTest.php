<?php

namespace Tourze\OAuth2ServerBundle\Tests\Entity;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

/**
 * OAuth2Client实体单元测试
 */
class OAuth2ClientTest extends TestCase
{
    private UserInterface&MockObject $mockUser;

    protected function setUp(): void
    {
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->method('getUserIdentifier')->willReturn('test@example.com');
    }

    public function test_constructor_createsEmptyCollections(): void
    {
        $client = new OAuth2Client();
        
        $this->assertCount(0, $client->getAuthorizationCodes());
        $this->assertSame([], $client->getRedirectUris());
        $this->assertSame(['client_credentials'], $client->getGrantTypes());
        $this->assertTrue($client->isConfidential());
        $this->assertTrue($client->isEnabled());
        $this->assertSame(3600, $client->getAccessTokenLifetime());
        $this->assertSame(1209600, $client->getRefreshTokenLifetime());
    }

    public function test_setClientId_andGetClientId(): void
    {
        $client = new OAuth2Client();
        $clientId = 'test_client_id';
        
        $result = $client->setClientId($clientId);
        
        $this->assertSame($client, $result);
        $this->assertSame($clientId, $client->getClientId());
    }

    public function test_setClientSecret_andGetClientSecret(): void
    {
        $client = new OAuth2Client();
        $secret = 'test_secret';
        
        $result = $client->setClientSecret($secret);
        
        $this->assertSame($client, $result);
        $this->assertSame($secret, $client->getClientSecret());
    }

    public function test_setName_andGetName(): void
    {
        $client = new OAuth2Client();
        $name = 'Test Application';
        
        $result = $client->setName($name);
        
        $this->assertSame($client, $result);
        $this->assertSame($name, $client->getName());
    }

    public function test_setDescription_andGetDescription(): void
    {
        $client = new OAuth2Client();
        $description = 'Test application description';
        
        $result = $client->setDescription($description);
        
        $this->assertSame($client, $result);
        $this->assertSame($description, $client->getDescription());
    }

    public function test_setUser_andGetUser(): void
    {
        $client = new OAuth2Client();
        
        $result = $client->setUser($this->mockUser);
        
        $this->assertSame($client, $result);
        $this->assertSame($this->mockUser, $client->getUser());
    }

    public function test_setRedirectUris_andGetRedirectUris(): void
    {
        $client = new OAuth2Client();
        $uris = ['https://example.com/callback', 'https://app.example.com/oauth'];
        
        $result = $client->setRedirectUris($uris);
        
        $this->assertSame($client, $result);
        $this->assertSame($uris, $client->getRedirectUris());
    }

    public function test_addRedirectUri_addsNewUri(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';
        
        $result = $client->addRedirectUri($uri);
        
        $this->assertSame($client, $result);
        $this->assertSame([$uri], $client->getRedirectUris());
    }

    public function test_addRedirectUri_doesNotAddDuplicate(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';
        
        $client->addRedirectUri($uri);
        $client->addRedirectUri($uri);
        
        $this->assertSame([$uri], $client->getRedirectUris());
    }

    public function test_removeRedirectUri_removesExistingUri(): void
    {
        $client = new OAuth2Client();
        $uri1 = 'https://example.com/callback';
        $uri2 = 'https://app.example.com/oauth';
        
        $client->setRedirectUris([$uri1, $uri2]);
        $result = $client->removeRedirectUri($uri1);
        
        $this->assertSame($client, $result);
        $this->assertSame([$uri2], array_values($client->getRedirectUris()));
    }

    public function test_removeRedirectUri_doesNothingForNonExistentUri(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';
        
        $client->setRedirectUris([$uri]);
        $client->removeRedirectUri('https://other.com/callback');
        
        $this->assertSame([$uri], $client->getRedirectUris());
    }

    public function test_hasRedirectUri_returnsTrueForExistingUri(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';
        
        $client->addRedirectUri($uri);
        
        $this->assertTrue($client->hasRedirectUri($uri));
    }

    public function test_hasRedirectUri_returnsFalseForNonExistentUri(): void
    {
        $client = new OAuth2Client();
        
        $this->assertFalse($client->hasRedirectUri('https://example.com/callback'));
    }

    public function test_setGrantTypes_andGetGrantTypes(): void
    {
        $client = new OAuth2Client();
        $grantTypes = ['authorization_code', 'refresh_token'];
        
        $result = $client->setGrantTypes($grantTypes);
        
        $this->assertSame($client, $result);
        $this->assertSame($grantTypes, $client->getGrantTypes());
    }

    public function test_supportsGrantType_returnsTrueForSupportedType(): void
    {
        $client = new OAuth2Client();
        $client->setGrantTypes(['authorization_code', 'refresh_token']);
        
        $this->assertTrue($client->supportsGrantType('authorization_code'));
        $this->assertTrue($client->supportsGrantType('refresh_token'));
    }

    public function test_supportsGrantType_returnsFalseForUnsupportedType(): void
    {
        $client = new OAuth2Client();
        $client->setGrantTypes(['authorization_code']);
        
        $this->assertFalse($client->supportsGrantType('client_credentials'));
    }

    public function test_setScopes_andGetScopes(): void
    {
        $client = new OAuth2Client();
        $scopes = ['read', 'write', 'admin'];
        
        $result = $client->setScopes($scopes);
        
        $this->assertSame($client, $result);
        $this->assertSame($scopes, $client->getScopes());
    }

    public function test_setScopes_withNull(): void
    {
        $client = new OAuth2Client();
        
        $result = $client->setScopes(null);
        
        $this->assertSame($client, $result);
        $this->assertNull($client->getScopes());
    }

    public function test_setConfidential_andIsConfidential(): void
    {
        $client = new OAuth2Client();
        
        $result = $client->setConfidential(false);
        
        $this->assertSame($client, $result);
        $this->assertFalse($client->isConfidential());
    }

    public function test_setEnabled_andIsEnabled(): void
    {
        $client = new OAuth2Client();
        
        $result = $client->setEnabled(false);
        
        $this->assertSame($client, $result);
        $this->assertFalse($client->isEnabled());
    }

    public function test_setAccessTokenLifetime_andGetAccessTokenLifetime(): void
    {
        $client = new OAuth2Client();
        $lifetime = 7200;
        
        $result = $client->setAccessTokenLifetime($lifetime);
        
        $this->assertSame($client, $result);
        $this->assertSame($lifetime, $client->getAccessTokenLifetime());
    }

    public function test_setRefreshTokenLifetime_andGetRefreshTokenLifetime(): void
    {
        $client = new OAuth2Client();
        $lifetime = 2592000;
        
        $result = $client->setRefreshTokenLifetime($lifetime);
        
        $this->assertSame($client, $result);
        $this->assertSame($lifetime, $client->getRefreshTokenLifetime());
    }

    public function test_setCodeChallengeMethod_andGetCodeChallengeMethod(): void
    {
        $client = new OAuth2Client();
        $methods = ['S256'];
        
        $result = $client->setCodeChallengeMethod($methods);
        
        $this->assertSame($client, $result);
        $this->assertSame($methods, $client->getCodeChallengeMethod());
    }

    public function test_supportsCodeChallengeMethod_returnsTrueForSupportedMethod(): void
    {
        $client = new OAuth2Client();
        $client->setCodeChallengeMethod(['plain', 'S256']);
        
        $this->assertTrue($client->supportsCodeChallengeMethod('plain'));
        $this->assertTrue($client->supportsCodeChallengeMethod('S256'));
    }

    public function test_supportsCodeChallengeMethod_returnsFalseForUnsupportedMethod(): void
    {
        $client = new OAuth2Client();
        $client->setCodeChallengeMethod(['S256']);
        
        $this->assertFalse($client->supportsCodeChallengeMethod('plain'));
    }

    public function test_supportsCodeChallengeMethod_returnsFalseWhenMethodsNull(): void
    {
        $client = new OAuth2Client();
        $client->setCodeChallengeMethod(null);
        
        $this->assertFalse($client->supportsCodeChallengeMethod('S256'));
    }

    public function test_addAuthorizationCode_addsCodeToCollection(): void
    {
        $client = new OAuth2Client();
        /** @var AuthorizationCode&MockObject $authCode */
        $authCode = $this->createMock(AuthorizationCode::class);
        $authCode->expects($this->once())->method('setClient')->with($client);
        
        $result = $client->addAuthorizationCode($authCode);
        
        $this->assertSame($client, $result);
        $this->assertCount(1, $client->getAuthorizationCodes());
        $this->assertTrue($client->getAuthorizationCodes()->contains($authCode));
    }

    public function test_addAuthorizationCode_doesNotAddDuplicate(): void
    {
        $client = new OAuth2Client();
        /** @var AuthorizationCode&MockObject $authCode */
        $authCode = $this->createMock(AuthorizationCode::class);
        $authCode->expects($this->once())->method('setClient')->with($client);
        
        $client->addAuthorizationCode($authCode);
        $client->addAuthorizationCode($authCode);
        
        $this->assertCount(1, $client->getAuthorizationCodes());
    }

    public function test_removeAuthorizationCode_removesCodeFromCollection(): void
    {
        $client = new OAuth2Client();
        /** @var AuthorizationCode&MockObject $authCode */
        $authCode = $this->createMock(AuthorizationCode::class);
        
        // 先添加到集合中
        $client->addAuthorizationCode($authCode);
        
        // 模拟getClient返回当前客户端，然后期望setClient被调用并传入null
        $authCode->method('getClient')->willReturn($client);
        $authCode->expects($this->once())->method('setClient')->with($this->isNull());
        
        $result = $client->removeAuthorizationCode($authCode);
        
        $this->assertSame($client, $result);
        $this->assertCount(0, $client->getAuthorizationCodes());
    }

    public function test_removeAuthorizationCode_doesNothingForNonExistentCode(): void
    {
        $client = new OAuth2Client();
        /** @var AuthorizationCode&MockObject $authCode */
        $authCode = $this->createMock(AuthorizationCode::class);
        $authCode->expects($this->never())->method('setClient');
        
        $result = $client->removeAuthorizationCode($authCode);
        
        $this->assertSame($client, $result);
    }

    public function test_toString_returnsName(): void
    {
        $client = new OAuth2Client();
        $client->setName('Test App');
        
        $this->assertSame('Test App', (string) $client);
    }

    public function test_toString_returnsClientIdWhenNameEmpty(): void
    {
        $client = new OAuth2Client();
        $client->setClientId('test_client');
        
        $this->assertSame('test_client', (string) $client);
    }

    public function test_toString_returnsEmptyStringWhenBothEmpty(): void
    {
        $client = new OAuth2Client();
        
        $this->assertSame('', (string) $client);
    }

    public function test_setCreateTime_andGetCreateTime(): void
    {
        $client = new OAuth2Client();
        $time = new \DateTime();
        
        $result = $client->setCreateTime($time);
        
        $this->assertSame($client, $result);
        $this->assertSame($time, $client->getCreateTime());
    }

    public function test_setUpdateTime_andGetUpdateTime(): void
    {
        $client = new OAuth2Client();
        $time = new \DateTime();
        
        $result = $client->setUpdateTime($time);
        
        $this->assertSame($client, $result);
        $this->assertSame($time, $client->getUpdateTime());
    }

    public function test_setCreateTime_withNull(): void
    {
        $client = new OAuth2Client();
        
        $result = $client->setCreateTime(null);
        
        $this->assertSame($client, $result);
        $this->assertNull($client->getCreateTime());
    }

    public function test_setUpdateTime_withNull(): void
    {
        $client = new OAuth2Client();
        
        $result = $client->setUpdateTime(null);
        
        $this->assertSame($client, $result);
        $this->assertNull($client->getUpdateTime());
    }
}
