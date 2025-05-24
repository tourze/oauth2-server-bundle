<?php

namespace Tourze\OAuth2ServerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineResolveTargetEntityBundle\Service\ResolveTargetEntityService;
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;

/**
 * OAuth2客户端测试数据
 */
class OAuth2ClientFixtures extends Fixture
{
    public function __construct(
        private readonly OAuth2ClientService $clientService,
        private readonly ResolveTargetEntityService $resolveTargetEntityService,
    ) {
    }

    /**
     * 创建模拟用户
     */
    private function createMockUser(): UserInterface
    {
        return $this->getReference('user-1', $this->resolveTargetEntityService->findEntityClass(UserInterface::class));
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->createMockUser();

        // 1. 创建基础Web应用客户端
        $webClient = $this->clientService->createClient(
            user: $user,
            name: 'Test Web Application',
            redirectUris: [
                'http://localhost:3000/callback',
                'https://example.com/oauth/callback'
            ],
            grantTypes: ['authorization_code', 'refresh_token'],
            description: '测试Web应用，支持授权码模式',
            scopes: ['read', 'write', 'admin']
        );
        $manager->persist($webClient);

        // 2. 创建API客户端（仅客户端凭证）
        $apiClient = $this->clientService->createClient(
            user: $user,
            name: 'API Service Client',
            redirectUris: [],
            grantTypes: ['client_credentials'],
            description: '后端API服务客户端，仅支持客户端凭证授权',
            scopes: ['api:read', 'api:write']
        );
        $manager->persist($apiClient);

        // 3. 创建移动应用客户端（支持PKCE）
        $mobileClient = $this->clientService->createClient(
            user: $user,
            name: 'Mobile App Client',
            redirectUris: [
                'myapp://oauth/callback',
                'https://app.example.com/callback'
            ],
            grantTypes: ['authorization_code', 'refresh_token'],
            description: '移动应用客户端，支持PKCE和授权码模式',
            scopes: ['profile', 'email', 'offline_access']
        );
        
        // 移动应用设置为公开客户端（不需要客户端密钥验证）
        $mobileClient->setConfidential(false);
        $manager->persist($mobileClient);

        // 4. 创建第三方集成客户端
        $integrationClient = $this->clientService->createClient(
            user: $user,
            name: 'Third Party Integration',
            redirectUris: [
                'https://partner.example.com/webhook',
                'https://integration.example.com/oauth/return'
            ],
            grantTypes: ['authorization_code', 'client_credentials', 'refresh_token'],
            description: '第三方系统集成客户端，支持多种授权方式',
            scopes: ['integration:read', 'integration:write', 'webhooks']
        );
        
        // 设置较长的令牌有效期
        $integrationClient->setAccessTokenLifetime(7200); // 2小时
        $integrationClient->setRefreshTokenLifetime(2592000); // 30天
        $manager->persist($integrationClient);

        // 5. 创建开发测试客户端
        $devClient = $this->clientService->createClient(
            user: $user,
            name: 'Development Test Client',
            redirectUris: [
                'http://localhost:8000/debug/oauth',
                'http://127.0.0.1:3000/callback',
                'postman://oauth/callback'
            ],
            grantTypes: ['authorization_code', 'client_credentials', 'refresh_token'],
            description: '开发环境测试客户端，支持所有授权类型',
            scopes: ['*'] // 所有权限
        );

        // 开发客户端设置较短的令牌有效期便于测试
        $devClient->setAccessTokenLifetime(1800); // 30分钟
        $manager->persist($devClient);

        $manager->flush();

        // 设置引用，供其他Fixture使用
        $this->addReference('oauth2-client-web', $webClient);
        $this->addReference('oauth2-client-api', $apiClient);
        $this->addReference('oauth2-client-mobile', $mobileClient);
        $this->addReference('oauth2-client-integration', $integrationClient);
        $this->addReference('oauth2-client-dev', $devClient);
    }
}
