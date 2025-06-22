<?php

namespace Tourze\OAuth2ServerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

/**
 * @method AuthorizationCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthorizationCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthorizationCode[]    findAll()
 * @method AuthorizationCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
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

        if ($authCode !== null && $authCode->isValid()) {
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
            ->where('ac.expiresAt < :now')
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->execute();
    }

    /**
     * 查找客户端的授权码
     */
    public function findByClient(OAuth2Client $client): array
    {
        return $this->findBy(['client' => $client], ['createTime' => 'DESC']);
    }

    /**
     * 保存授权码
     */
    public function save(AuthorizationCode $authCode, bool $flush = true): void
    {
        $this->getEntityManager()->persist($authCode);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除授权码
     */
    public function remove(AuthorizationCode $authCode, bool $flush = true): void
    {
        $this->getEntityManager()->remove($authCode);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
