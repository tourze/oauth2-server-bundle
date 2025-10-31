<?php

namespace Tourze\OAuth2ServerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

class OAuth2AccessLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 创建一些测试用的访问日志
        $endpoints = ['/token', '/authorize', '/userinfo', '/revoke'];
        $statuses = ['success', 'error', 'invalid_request', 'invalid_client'];
        $errorCodes = ['001', '002', '003', '004', null];

        for ($i = 1; $i <= 20; ++$i) {
            $clientIndex = ($i - 1) % 15; // 循环使用现有的15个客户端 (0-2 + 4-15 = 14个)
            if ($clientIndex >= 3) {
                ++$clientIndex; // 跳过索引3，因为只有0-2和4-15
            }
            $client = $this->getReference('oauth2_client_' . $clientIndex, OAuth2Client::class);
            $user = $client->getUser();

            // 使用 UserInterface 的标准方法获取用户标识符
            $userId = $user?->getUserIdentifier() ?? 'anonymous';

            // 创建访问日志
            $log = OAuth2AccessLog::create(
                $endpoints[array_rand($endpoints)],
                '192.168.1.' . random_int(1, 255),
                ['GET', 'POST'][array_rand(['GET', 'POST'])],
                $statuses[array_rand($statuses)],
                $client->getClientId(),
                $client,
                $userId
            );

            // 随机设置错误码
            if (1 === random_int(0, 1)) {
                $log->setErrorCode($errorCodes[array_rand($errorCodes)]);
            }

            $manager->persist($log);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OAuth2ClientFixtures::class,
        ];
    }
}
