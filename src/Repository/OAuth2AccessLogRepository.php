<?php

namespace Tourze\OAuth2ServerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * OAuth2访问日志Repository
 *
 * 提供访问日志的查询、统计和清理功能
 *
 * @extends ServiceEntityRepository<OAuth2AccessLog>
 */
#[AsRepository(entityClass: OAuth2AccessLog::class)]
class OAuth2AccessLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuth2AccessLog::class);
    }

    /**
     * 批量保存访问日志
     * @param array<OAuth2AccessLog> $logs
     */
    public function saveBatch(array $logs): void
    {
        $em = $this->getEntityManager();
        foreach ($logs as $log) {
            $em->persist($log);
        }
        $em->flush();
    }

    /**
     * 根据端点统计访问次数
     */
    public function getAccessCountByEndpoint(string $endpoint, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): int
    {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.endpoint = :endpoint')
            ->setParameter('endpoint', $endpoint)
        ;

        if (null !== $from) {
            $qb->andWhere('l.createTime >= :from')
                ->setParameter('from', $from)
            ;
        }

        if (null !== $to) {
            $qb->andWhere('l.createTime <= :to')
                ->setParameter('to', $to)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根据客户端统计访问次数
     */
    public function getAccessCountByClient(OAuth2Client $client, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): int
    {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.client = :client')
            ->setParameter('client', $client)
        ;

        if (null !== $from) {
            $qb->andWhere('l.createTime >= :from')
                ->setParameter('from', $from)
            ;
        }

        if (null !== $to) {
            $qb->andWhere('l.createTime <= :to')
                ->setParameter('to', $to)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根据IP地址统计访问次数
     */
    public function getAccessCountByIp(string $ipAddress, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): int
    {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.ipAddress = :ipAddress')
            ->setParameter('ipAddress', $ipAddress)
        ;

        if (null !== $from) {
            $qb->andWhere('l.createTime >= :from')
                ->setParameter('from', $from)
            ;
        }

        if (null !== $to) {
            $qb->andWhere('l.createTime <= :to')
                ->setParameter('to', $to)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 获取错误访问记录
     * @return array<OAuth2AccessLog>
     */
    public function getErrorLogs(int $limit = 100, ?\DateTimeInterface $from = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', 'error')
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
        ;

        if (null !== $from) {
            $qb->andWhere('l.createTime >= :from')
                ->setParameter('from', $from)
            ;
        }

        /** @var array<OAuth2AccessLog> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取热门端点统计
     * @return array<array{endpoint: string, access_count: int}>
     */
    public function getPopularEndpoints(int $limit = 10, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l.endpoint, COUNT(l.id) as access_count')
            ->groupBy('l.endpoint')
            ->orderBy('access_count', 'DESC')
            ->setMaxResults($limit)
        ;

        if (null !== $from) {
            $qb->andWhere('l.createTime >= :from')
                ->setParameter('from', $from)
            ;
        }

        if (null !== $to) {
            $qb->andWhere('l.createTime <= :to')
                ->setParameter('to', $to)
            ;
        }

        /** @var array<array{endpoint: string, access_count: int}> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取热门客户端统计
     * @return array<array{name: string, clientId: string, access_count: int}>
     */
    public function getPopularClients(int $limit = 10, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('c.name, c.clientId, COUNT(l.id) as access_count')
            ->leftJoin('l.client', 'c')
            ->where('l.client IS NOT NULL')
            ->groupBy('c.id')
            ->orderBy('access_count', 'DESC')
            ->setMaxResults($limit)
        ;

        if (null !== $from) {
            $qb->andWhere('l.createTime >= :from')
                ->setParameter('from', $from)
            ;
        }

        if (null !== $to) {
            $qb->andWhere('l.createTime <= :to')
                ->setParameter('to', $to)
            ;
        }

        /** @var array<array{name: string, clientId: string, access_count: int}> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 检测可疑IP（高频访问）
     * @return array<array{ipAddress: string, access_count: int}>
     */
    public function getSuspiciousIps(int $threshold = 100, ?\DateTimeInterface $from = null): array
    {
        $from ??= new \DateTime('-1 hour');

        /** @var array<array{ipAddress: string, access_count: int}> */
        return $this->createQueryBuilder('l')
            ->select('l.ipAddress, COUNT(l.id) as access_count')
            ->where('l.createTime >= :from')
            ->setParameter('from', $from)
            ->groupBy('l.ipAddress')
            ->having('access_count > :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('access_count', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 清理过期的访问日志
     */
    public function cleanupOldLogs(\DateTimeInterface $before): int
    {
        $result = $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createTime < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;

        return is_int($result) ? $result : 0;
    }

    /**
     * 获取日访问量统计
     * @return array<array{date: string, total_count: int, success_count: int, error_count: int}>
     */
    public function getDailyStats(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $from ??= new \DateTime('-30 days');
        $to ??= new \DateTime();

        /** @var array<array{date: string, total_count: int, success_count: int, error_count: int}> */
        return $this->createQueryBuilder('l')
            ->select('DATE(l.createTime) as date, COUNT(l.id) as total_count,
                     SUM(CASE WHEN l.status = \'success\' THEN 1 ELSE 0 END) as success_count,
                     SUM(CASE WHEN l.status = \'error\' THEN 1 ELSE 0 END) as error_count')
            ->where('l.createTime BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取平均响应时间
     */
    public function getAverageResponseTime(?string $endpoint = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): ?float
    {
        $qb = $this->createQueryBuilder('l')
            ->select('AVG(l.responseTime)')
            ->where('l.responseTime IS NOT NULL')
        ;

        if (null !== $endpoint) {
            $qb->andWhere('l.endpoint = :endpoint')
                ->setParameter('endpoint', $endpoint)
            ;
        }

        if (null !== $from) {
            $qb->andWhere('l.createTime >= :from')
                ->setParameter('from', $from)
            ;
        }

        if (null !== $to) {
            $qb->andWhere('l.createTime <= :to')
                ->setParameter('to', $to)
            ;
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }

    public function save(OAuth2AccessLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OAuth2AccessLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
