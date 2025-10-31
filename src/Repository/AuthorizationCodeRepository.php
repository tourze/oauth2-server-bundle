<?php

namespace Tourze\OAuth2ServerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AuthorizationCode>
 */
#[AsRepository(entityClass: AuthorizationCode::class)]
class AuthorizationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthorizationCode::class);
    }

    /**
     * 根据授权码查找记录
     */
    public function findByCode(string $code): ?AuthorizationCode
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * 查找有效的授权码
     */
    public function findValidByCode(string $code): ?AuthorizationCode
    {
        $authCode = $this->findByCode($code);

        if (null !== $authCode && $authCode->isValid()) {
            return $authCode;
        }

        return null;
    }

    /**
     * 清理过期的授权码
     */
    public function removeExpiredCodes(): int
    {
        $qb = $this->createQueryBuilder('ac')
            ->delete()
            ->where('ac.expireTime < :now')
            ->setParameter('now', new \DateTime())
        ;

        $result = $qb->getQuery()->execute();

        return is_int($result) ? $result : 0;
    }

    /**
     * 查找客户端的授权码
     * @return array<AuthorizationCode>
     */
    public function findByClient(OAuth2Client $client): array
    {
        return $this->findBy(['client' => $client], ['createTime' => 'DESC']);
    }

    public function save(AuthorizationCode $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuthorizationCode $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
