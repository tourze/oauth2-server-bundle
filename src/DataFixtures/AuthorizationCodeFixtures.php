<?php

namespace Tourze\OAuth2ServerBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

class AuthorizationCodeFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        // 使用现有的客户端创建授权码
        for ($i = 0; $i < 3; ++$i) {
            $client = $this->getReference('oauth2_client_' . $i, OAuth2Client::class);

            // 创建授权码，User 字段设为 null
            $authCode = AuthorizationCode::create(
                $client,
                null, // User 字段设为 null，实际应用中应该关联到真实的 User 实体
                'https://localhost:8000/callback',
                ['read', 'write'],
                30, // 30分钟过期
                'challenge_' . $i,
                'S256',
                'state_' . $i
            );
            $manager->persist($authCode);
        }

        // 创建一些没有某些可选字段的授权码
        for ($i = 4; $i < 7; ++$i) {
            $client = $this->getReference('oauth2_client_' . $i, OAuth2Client::class);

            // 创建没有 code challenge 的授权码
            $authCode = AuthorizationCode::create(
                $client,
                null, // User 字段设为 null，实际应用中应该关联到真实的 User 实体
                'https://localhost:8000/callback',
                null, // 没有 scopes
                10
            );
            $manager->persist($authCode);
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
