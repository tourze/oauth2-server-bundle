<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Repository\AuthorizationCodeRepository;

class AuthorizationCodeRepositoryTest extends TestCase
{
    private ManagerRegistry&MockObject $managerRegistry;
    private AuthorizationCodeRepository $repository;

    public function testExtendsServiceEntityRepository(): void
    {
        self::assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(AuthorizationCodeRepository::class, $this->repository);
    }

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->repository = new AuthorizationCodeRepository($this->managerRegistry);
    }
}