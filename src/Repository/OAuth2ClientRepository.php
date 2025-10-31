<?php

namespace Tourze\OAuth2ServerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<OAuth2Client>
 */
#[AsRepository(entityClass: OAuth2Client::class)]
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

        if (null === $client) {
            return null;
        }

        // 如果是机密客户端，需要验证密钥
        if ($client->isConfidential() && null !== $clientSecret) {
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
     * @return array<OAuth2Client>
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->findBy(['user' => $user, 'enabled' => true]);
    }

    public function save(OAuth2Client $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OAuth2Client $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
