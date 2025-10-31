<?php

namespace Tourze\OAuth2ServerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * OAuth2ClientService集成测试
 *
 * @internal
 */
#[CoversClass(OAuth2ClientService::class)]
#[RunTestsInSeparateProcesses]
final class OAuth2ClientServiceTest extends AbstractIntegrationTestCase
{
    private UserInterface $testUser;

    private OAuth2ClientService $clientService;

    protected function onSetUp(): void
    {
        // 创建测试用户
        $this->testUser = $this->createNormalUser('test@example.com', 'password123');

        // 从容器获取服务实例
        $this->clientService = self::getService(OAuth2ClientService::class);
    }

    public function testCreateClientWithAllParameters(): void
    {
        $name = 'Test Application';
        $redirectUris = ['https://example.com/callback'];
        $grantTypes = ['authorization_code', 'refresh_token'];
        $description = 'Test application description';
        $scopes = ['read', 'write'];

        $client = $this->clientService->createClient(
            $this->testUser,
            $name,
            $redirectUris,
            $grantTypes,
            $description,
            true,
            $scopes
        );

        $this->assertSame($this->testUser, $client->getUser());
        $this->assertSame($name, $client->getName());
        $this->assertSame($redirectUris, $client->getRedirectUris());
        $this->assertSame($grantTypes, $client->getGrantTypes());
        $this->assertSame($description, $client->getDescription());
        $this->assertTrue($client->isConfidential());
        $this->assertSame($scopes, $client->getScopes());
        $this->assertNotEmpty($client->getClientId());
        $this->assertNotEmpty($client->getClientSecret());
        $this->assertStringStartsWith('client_', $client->getClientId());

        // 验证实体已持久化
        $this->assertEntityPersisted($client);
    }

    public function testCreateClientWithMinimalParameters(): void
    {
        $name = 'Simple Client';

        $client = $this->clientService->createClient($this->testUser, $name);

        $this->assertSame($name, $client->getName());
        $this->assertSame([], $client->getRedirectUris());
        $this->assertSame(['client_credentials'], $client->getGrantTypes());
        $this->assertNull($client->getDescription());
        $this->assertTrue($client->isConfidential());
        $this->assertNull($client->getScopes());

        // 验证实体已持久化
        $this->assertEntityPersisted($client);
    }

    public function testCreateClientGeneratesUniqueClientId(): void
    {
        // 创建第一个客户端
        $client1 = $this->clientService->createClient($this->testUser, 'First Client');
        $clientId1 = $client1->getClientId();

        // 创建第二个客户端
        $client2 = $this->clientService->createClient($this->testUser, 'Second Client');
        $clientId2 = $client2->getClientId();

        // 验证客户端ID是唯一的
        $this->assertNotEmpty($clientId1);
        $this->assertNotEmpty($clientId2);
        $this->assertNotSame($clientId1, $clientId2);

        // 验证两个实体都已持久化
        $this->assertEntityPersisted($client1);
        $this->assertEntityPersisted($client2);
    }

    public function testValidateClientWithValidConfidentialClient(): void
    {
        // 创建一个机密客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Confidential Client',
            ['https://example.com/callback'],
            ['authorization_code'],
            null,
            true  // confidential
        );

        $clientId = $client->getClientId();
        // createClient返回时，entity中包含明文密钥（仅此一次机会获取）
        $clientSecret = $client->getClientSecret();

        // 确保明文密钥非空
        $this->assertNotEmpty($clientSecret);

        // 清除EntityManager，确保后续查询从数据库重新加载
        self::getEntityManager()->clear();

        // 验证客户端
        $result = $this->clientService->validateClient($clientId, $clientSecret);

        $this->assertInstanceOf(OAuth2Client::class, $result);
        $this->assertSame($clientId, $result->getClientId());
    }

    public function testValidateClientWithInvalidClientSecret(): void
    {
        // 创建一个机密客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Confidential Client',
            ['https://example.com/callback'],
            ['authorization_code'],
            null,
            true  // confidential
        );

        $clientId = $client->getClientId();
        $wrongSecret = 'wrong_secret_456';

        // 清除EntityManager，确保后续查询从数据库重新加载
        self::getEntityManager()->clear();

        // 验证客户端（使用错误的密钥）
        $result = $this->clientService->validateClient($clientId, $wrongSecret);

        $this->assertNull($result);
    }

    public function testValidateClientWithPublicClient(): void
    {
        // 创建一个公共客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Public Client',
            ['https://example.com/callback'],
            ['authorization_code'],
            null,
            false  // public client
        );

        $clientId = $client->getClientId();

        // 清除EntityManager，确保后续查询从数据库重新加载
        self::getEntityManager()->clear();

        // 验证公共客户端（无需密钥）
        $result = $this->clientService->validateClient($clientId);

        $this->assertInstanceOf(OAuth2Client::class, $result);
        $this->assertSame($clientId, $result->getClientId());
    }

    public function testValidateClientWithNonExistentClient(): void
    {
        $clientId = 'non_existent_client_id';

        $result = $this->clientService->validateClient($clientId, 'any_secret');

        $this->assertNull($result);
    }

    public function testValidateClientConfidentialClientWithoutSecret(): void
    {
        // 创建一个机密客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Confidential Client',
            ['https://example.com/callback'],
            ['authorization_code'],
            null,
            true  // confidential
        );

        $clientId = $client->getClientId();

        // 清除EntityManager，确保后续查询从数据库重新加载
        self::getEntityManager()->clear();

        // 验证机密客户端但不提供密钥
        $result = $this->clientService->validateClient($clientId);

        $this->assertNull($result);
    }

    public function testVerifyClientSecretWithCorrectSecret(): void
    {
        // 创建客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Secret Verification',
            ['https://example.com/callback'],
            ['authorization_code'],
            null,
            true
        );

        $clientId = $client->getClientId();
        // createClient返回时，entity中包含明文密钥（仅此一次机会获取）
        $plainSecret = $client->getClientSecret();

        // 清除EntityManager并重新加载，以获取数据库中的哈希密钥
        self::getEntityManager()->clear();
        $reloadedClient = self::getEntityManager()->getRepository(OAuth2Client::class)->findOneBy(['clientId' => $clientId]);
        $this->assertInstanceOf(OAuth2Client::class, $reloadedClient);

        // 验证客户端密钥（使用重新加载的entity，其中包含哈希后的密钥）
        $result = $this->clientService->verifyClientSecret($reloadedClient, $plainSecret);

        $this->assertTrue($result);
    }

    public function testVerifyClientSecretWithIncorrectSecret(): void
    {
        // 创建客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Wrong Secret',
            ['https://example.com/callback'],
            ['authorization_code'],
            null,
            true
        );

        $clientId = $client->getClientId();

        // 清除EntityManager并重新加载，以获取数据库中的哈希密钥
        self::getEntityManager()->clear();
        $reloadedClient = self::getEntityManager()->getRepository(OAuth2Client::class)->findOneBy(['clientId' => $clientId]);
        $this->assertInstanceOf(OAuth2Client::class, $reloadedClient);

        $wrongSecret = 'completely_wrong_secret';

        // 验证错误的密钥（使用重新加载的entity）
        $result = $this->clientService->verifyClientSecret($reloadedClient, $wrongSecret);

        $this->assertFalse($result);
    }

    public function testValidateRedirectUriWithExactMatch(): void
    {
        $redirectUri = 'https://example.com/callback';

        // 创建具有特定重定向URI的客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Redirect URI',
            [$redirectUri],
            ['authorization_code']
        );

        // 验证精确匹配的URI
        $result = $this->clientService->validateRedirectUri($client, $redirectUri);

        $this->assertTrue($result);
    }

    public function testValidateRedirectUriWithSubpathMatch(): void
    {
        $allowedUri = 'https://example.com';
        $requestedUri = 'https://example.com/callback';

        // 创建客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Subpath URI',
            [$allowedUri],
            ['authorization_code']
        );

        // 验证子路径匹配
        $result = $this->clientService->validateRedirectUri($client, $requestedUri);

        $this->assertTrue($result);
    }

    public function testValidateRedirectUriWithNonMatchingUri(): void
    {
        $allowedUri = 'https://example.com/callback';
        $requestedUri = 'https://malicious.com/callback';

        // 创建客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Non-matching URI',
            [$allowedUri],
            ['authorization_code']
        );

        // 验证不匹配的URI
        $result = $this->clientService->validateRedirectUri($client, $requestedUri);

        $this->assertFalse($result);
    }

    public function testValidateRedirectUriWithEmptyAllowedUris(): void
    {
        $requestedUri = 'https://example.com/callback';

        // 创建没有重定向URI的客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client with Empty URIs',
            [],  // 空的重定向URI列表
            ['client_credentials']
        );

        // 验证请求的URI
        $result = $this->clientService->validateRedirectUri($client, $requestedUri);

        $this->assertFalse($result);
    }

    public function testSupportsGrantTypeReturnsTrueForSupportedType(): void
    {
        $grantType = 'authorization_code';

        // 创建支持特定授权类型的客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Grant Type',
            ['https://example.com/callback'],
            [$grantType, 'refresh_token']
        );

        // 验证支持的授权类型
        $result = $this->clientService->supportsGrantType($client, $grantType);

        $this->assertTrue($result);
    }

    public function testSupportsGrantTypeReturnsFalseForUnsupportedType(): void
    {
        $grantType = 'password';

        // 创建不支持password授权类型的客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Unsupported Grant',
            ['https://example.com/callback'],
            ['authorization_code']  // 只支持authorization_code
        );

        // 验证不支持的授权类型
        $result = $this->clientService->supportsGrantType($client, $grantType);

        $this->assertFalse($result);
    }

    public function testUpdateClientSavesClient(): void
    {
        // 创建客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Original Name',
            ['https://example.com/callback'],
            ['authorization_code']
        );

        // 修改客户端属性
        $newName = 'Updated Name';
        $client->setName($newName);

        // 更新客户端
        $this->clientService->updateClient($client);

        // 验证更新已保存
        $this->assertEntityPersisted($client);
        $this->assertSame($newName, $client->getName());
    }

    public function testRegenerateClientSecretGeneratesNewSecret(): void
    {
        // 创建客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Secret Regeneration',
            ['https://example.com/callback'],
            ['authorization_code'],
            null,
            true
        );

        $oldSecret = $client->getClientSecret();

        // 重新生成密钥
        $newSecret = $this->clientService->regenerateClientSecret($client);

        // 验证新密钥
        $this->assertNotEmpty($newSecret);
        $this->assertNotSame($oldSecret, $client->getClientSecret());

        // 验证实体已更新并持久化
        $this->assertEntityPersisted($client);
    }

    public function testDisableClientSetsEnabledToFalse(): void
    {
        // 创建启用的客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Disable',
            ['https://example.com/callback'],
            ['authorization_code']
        );

        // 禁用客户端
        $this->clientService->disableClient($client);

        // 验证客户端已禁用
        $this->assertFalse($client->isEnabled());
        $this->assertEntityPersisted($client);
    }

    public function testEnableClientSetsEnabledToTrue(): void
    {
        // 创建禁用的客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Enable',
            ['https://example.com/callback'],
            ['authorization_code']
        );

        // 先禁用
        $this->clientService->disableClient($client);
        $this->assertFalse($client->isEnabled());

        // 再启用
        $this->clientService->enableClient($client);

        // 验证客户端已启用
        $this->assertTrue($client->isEnabled());
        $this->assertEntityPersisted($client);
    }

    public function testDeleteClientRemovesClient(): void
    {
        // 创建客户端
        $client = $this->clientService->createClient(
            $this->testUser,
            'Test Client for Deletion',
            ['https://example.com/callback'],
            ['authorization_code']
        );

        $clientId = $client->getId();

        // 删除客户端
        $this->clientService->deleteClient($client);

        // 验证客户端已删除
        $this->assertEntityNotExists(OAuth2Client::class, $clientId);
    }

    public function testGetClientsByUserReturnsUserClients(): void
    {
        // 创建多个客户端
        $client1 = $this->clientService->createClient($this->testUser, 'Client 1');
        $client2 = $this->clientService->createClient($this->testUser, 'Client 2');

        // 获取用户的所有客户端
        $clients = $this->clientService->getClientsByUser($this->testUser);

        // 验证返回的客户端列表
        $this->assertCount(2, $clients);
        $this->assertContains($client1, $clients);
        $this->assertContains($client2, $clients);
    }
}
