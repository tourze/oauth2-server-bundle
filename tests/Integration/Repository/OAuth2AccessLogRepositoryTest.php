<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Repository\OAuth2AccessLogRepository;

class OAuth2AccessLogRepositoryTest extends TestCase
{
    private ManagerRegistry&MockObject $managerRegistry;
    private OAuth2AccessLogRepository $repository;

    public function testExtendsServiceEntityRepository(): void
    {
        self::assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(OAuth2AccessLogRepository::class, $this->repository);
    }

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->repository = new OAuth2AccessLogRepository($this->managerRegistry);
    }
}