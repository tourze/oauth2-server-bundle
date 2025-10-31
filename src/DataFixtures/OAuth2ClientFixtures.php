<?php

namespace Tourze\OAuth2ServerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

class OAuth2ClientFixtures extends Fixture
{
    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 由于 Doctrine 的架构限制，无法在 DataFixtures 中使用匿名类
        // 实际的 User 实体应该由用户应用提供，这里我们只创建 OAuth2Client
        // 客户端可以在运行时关联到真实的 User 实体

        // 创建一些测试用的 OAuth2 客户端
        $clients = [
            [
                'client_id' => 'test_client_1',
                'client_secret' => 'secret_1',
                'name' => 'Test Web Application',
                'redirect_uris' => ['https://localhost:8000/callback', 'https://localhost:8000/auth'],
                'grant_types' => ['authorization_code', 'refresh_token'],
            ],
            [
                'client_id' => 'mobile_app_1',
                'client_secret' => 'mobile_secret_1',
                'name' => 'Mobile Application',
                'redirect_uris' => ['com.example.app://callback'],
                'grant_types' => ['authorization_code'],
            ],
            [
                'client_id' => 'api_client_1',
                'client_secret' => 'api_secret_1',
                'name' => 'API Service Client',
                'redirect_uris' => ['https://localhost:8000/api/callback'],
                'grant_types' => ['client_credentials'],
            ],
        ];

        foreach ($clients as $i => $clientData) {
            $client = new OAuth2Client();
            $client->setClientId($clientData['client_id']);
            $client->setClientSecret($clientData['client_secret']);
            $client->setName($clientData['name']);
            // User 字段设为 null，实际应用中应该关联到真实的 User 实体
            $client->setUser(null);
            $client->setRedirectUris($clientData['redirect_uris']);
            $client->setGrantTypes($clientData['grant_types']);

            $manager->persist($client);

            // 添加引用，方便其他 fixtures 使用
            $this->addReference('oauth2_client_' . $i, $client);
        }

        // 创建更多随机客户端用于测试
        for ($i = 4; $i <= 15; ++$i) {
            $client = new OAuth2Client();
            $client->setClientId("auto_client_{$i}");
            $client->setClientSecret("auto_secret_{$i}");
            $client->setName("Auto Generated Client {$i}");
            // User 字段设为 null，实际应用中应该关联到真实的 User 实体
            $client->setUser(null);
            $client->setRedirectUris(['https://localhost:8000/callback']);
            $client->setGrantTypes(['authorization_code']);

            $manager->persist($client);

            $this->addReference('oauth2_client_' . $i, $client);
        }

        $manager->flush();
    }
}
