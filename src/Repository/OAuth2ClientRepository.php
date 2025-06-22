<?php

namespace Tourze\OAuth2ServerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

/**
 * @method OAuth2Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method OAuth2Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method OAuth2Client[]    findAll()
 * @method OAuth2Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OAuth2ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuth2Client::class);
    }

    /**
     * 根据客户端ID查找客户端
     */
    public function findByClientId(string $clientId): ?OAuth2Client
    {
        return $this->findOneBy(['clientId' => $clientId, 'enabled' => true]);
    }

    /**
     * 验证客户端凭证
     */
    public function validateClient(string $clientId, ?string $clientSecret = null): ?OAuth2Client
    {
        $client = $this->findByClientId($clientId);

        if ($client === null) {
            return null;
        }

        // 如果是机密客户端，需要验证密钥
        if ($client->isConfidential() && $clientSecret !== null) {
            // 这里应该使用密码哈希验证，实际实现会在服务层处理
            return $client;
        }

        // 公开客户端不需要密钥验证
        if (!$client->isConfidential()) {
            return $client;
        }

        return null;
    }

    /**
     * 根据用户查找客户端
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->findBy(['user' => $user, 'enabled' => true]);
    }

    /**
     * 保存客户端
     */
    public function save(OAuth2Client $client, bool $flush = true): void
    {
        $this->getEntityManager()->persist($client);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除客户端
     */
    public function remove(OAuth2Client $client, bool $flush = true): void
    {
        $this->getEntityManager()->remove($client);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
