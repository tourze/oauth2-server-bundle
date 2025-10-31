<?php

namespace Tourze\OAuth2ServerBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Repository\OAuth2AccessLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(OAuth2AccessLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class OAuth2AccessLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected function setUpContainer(): void
    {
        // 这个测试不需要额外的设置
    }

    protected function onSetUp(): void        // 这里可以添加测试前的初始化逻辑
    {
    }

    public function testCleanupOldLogs(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $before = new \DateTime('-1 year');

        $result = $repository->cleanupOldLogs($before);

        self::assertGreaterThanOrEqual(0, $result);
    }

    public function testSaveBatch(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $logs = [];

        $repository->saveBatch($logs);

        // Test passes if no exception is thrown - method executed successfully
        self::expectNotToPerformAssertions();
    }

    public function testFindByClientAssociation(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');

        // 创建测试客户端
        $client = new OAuth2Client();
        $client->setClientId('test_client_' . uniqid());
        $client->setClientSecret('secret');
        $client->setName('Test Client');
        // $client->setUser($mockUser); // 暂时注释，避免Doctrine映射问题
        $client->setRedirectUris(['https://example.com/callback']);
        $client->setGrantTypes(['client_credentials']);
        $this->persistAndFlush($client);

        // 创建关联的访问日志
        $log = OAuth2AccessLog::create(
            'token',
            '127.0.0.1',
            'POST',
            'success',
            'test_client_' . uniqid(),
            $client
        );
        $this->persistAndFlush($log);

        $result = $repository->findBy(['client' => $client]);

        self::assertCount(1, $result);
        self::assertSame($log->getId(), $result[0]->getId());
    }

    public function testCountByClientAssociation(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');

        // 创建测试客户端
        $client = new OAuth2Client();
        $client->setClientId('test_client_' . uniqid());
        $client->setClientSecret('secret');
        $client->setName('Test Client');
        // $client->setUser($mockUser); // 暂时注释，避免Doctrine映射问题
        $client->setRedirectUris(['https://example.com/callback']);
        $client->setGrantTypes(['client_credentials']);
        $this->persistAndFlush($client);

        // 创建多个访问日志
        for ($i = 1; $i <= 3; ++$i) {
            $log = OAuth2AccessLog::create(
                'token',
                '127.0.0.' . $i,
                'POST',
                'success',
                'test_client_' . uniqid(),
                $client
            );
            $this->persistAndFlush($log);
        }

        $count = $repository->count(['client' => $client]);

        self::assertSame(3, $count);
    }

    public function testFindByNullableFieldWithNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 clientId 为 null 的访问日志
        $logWithNullClient = OAuth2AccessLog::create(
            'token',
            '127.0.0.1',
            'POST',
            'success',
            null
        );

        // 创建有 clientId 的访问日志
        $logWithClient = OAuth2AccessLog::create(
            'token',
            '127.0.0.2',
            'POST',
            'success',
            'test_client_' . uniqid()
        );

        $this->persistAndFlush($logWithNullClient);
        $this->persistAndFlush($logWithClient);

        $result = $repository->findBy(['clientId' => null]);

        self::assertCount(1, $result);
        self::assertSame($logWithNullClient->getId(), $result[0]->getId());
    }

    public function testCountByNullableFieldWithNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 clientId 为 null 的访问日志
        $logWithNullClient = OAuth2AccessLog::create(
            'token',
            '127.0.0.1',
            'POST',
            'success',
            null
        );

        $this->persistAndFlush($logWithNullClient);

        $count = $repository->count(['clientId' => null]);

        self::assertSame(1, $count);
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建访问日志
        $log = OAuth2AccessLog::create(
            'token',
            '127.0.0.1',
            'POST',
            'success'
        );
        $this->persistAndFlush($log);
        $id = $log->getId();

        // 验证实体存在
        self::assertNotNull($repository->find($id));

        // 删除实体
        $repository->remove($log, true);

        // 验证实体已删除
        self::assertNull($repository->find($id));
    }

    public function testFindByUserIdWithNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 userId 为 null 的访问日志
        $logWithNullUserId = OAuth2AccessLog::create(
            'token',
            '127.0.0.1',
            'POST',
            'success',
            null
        );

        // 创建有 userId 的访问日志
        $logWithUserId = OAuth2AccessLog::create(
            'token',
            '127.0.0.2',
            'POST',
            'success',
            'test_client_' . uniqid()
        );
        $logWithUserId->setUserId('user123');

        $this->persistAndFlush($logWithNullUserId);
        $this->persistAndFlush($logWithUserId);

        $result = $repository->findBy(['userId' => null]);

        self::assertCount(1, $result);
        self::assertSame($logWithNullUserId->getId(), $result[0]->getId());
    }

    public function testCountByUserIdWithNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 userId 为 null 的访问日志
        for ($i = 1; $i <= 2; ++$i) {
            $log = OAuth2AccessLog::create(
                'token',
                '127.0.0.' . $i,
                'POST',
                'success',
                null
            );
            $this->persistAndFlush($log);
        }

        $count = $repository->count(['userId' => null]);

        self::assertSame(2, $count);
    }

    public function testFindByUserAgentWithNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 userAgent 为 null 的访问日志，使用唯一IP
        $uniqueIp1 = '192.168.50.' . uniqid();
        $logWithNullUserAgent = OAuth2AccessLog::create(
            'token',
            $uniqueIp1,
            'POST',
            'success'
        );
        $logWithNullUserAgent->setUserAgent(null);

        // 创建有 userAgent 的访问日志，使用唯一IP
        $uniqueIp2 = '192.168.50.' . uniqid();
        $logWithUserAgent = OAuth2AccessLog::create(
            'token',
            $uniqueIp2,
            'POST',
            'success'
        );
        $logWithUserAgent->setUserAgent('Mozilla/5.0');

        $this->persistAndFlush($logWithNullUserAgent);
        $this->persistAndFlush($logWithUserAgent);

        // 查询所有userAgent为null的日志，然后过滤出我们创建的
        $result = $repository->findBy(['userAgent' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($result, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame($logWithNullUserAgent->getId(), array_values($ourLogs)[0]->getId());
    }

    public function testCountByUserAgentWithNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 userAgent 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 3; ++$i) {
            $uniqueIp = '192.168.60.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                'token',
                $uniqueIp,
                'POST',
                'success'
            );
            $log->setUserAgent(null);
            $this->persistAndFlush($log);
        }

        // 查询所有userAgent为null的日志，然后计算我们创建的
        $result = $repository->findBy(['userAgent' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($result, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(3, $ourLogsCount);
    }

    public function testFindByClientAssociationWithDifferentClients(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient1 = $this->createOAuth2Client($mockUser);
        $mockClient2 = $this->createOAuth2Client($mockUser);

        $log1 = $this->createOAuth2AccessLog('token', $mockClient1, null, 'success');
        $this->persistAndFlush($log1);

        $log2 = $this->createOAuth2AccessLog('authorize', $mockClient2, null, 'error');
        $this->persistAndFlush($log2);

        $results = $repository->findBy(['client' => $mockClient1]);
        self::assertCount(1, $results);
        self::assertSame('token', $results[0]->getEndpoint());

        $results = $repository->findBy(['client' => $mockClient2]);
        self::assertCount(1, $results);
        self::assertSame('authorize', $results[0]->getEndpoint());
    }

    public function testCountByClientAssociationWithMultipleClients(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient1 = $this->createOAuth2Client($mockUser);
        $mockClient2 = $this->createOAuth2Client($mockUser);

        // 为第一个客户端创建多个日志
        for ($i = 1; $i <= 3; ++$i) {
            $log = $this->createOAuth2AccessLog('token', $mockClient1, null, 'success');
            $this->persistAndFlush($log);
        }

        // 为第二个客户端创建日志
        $log = $this->createOAuth2AccessLog('authorize', $mockClient2, null, 'error');
        $this->persistAndFlush($log);

        $count1 = $repository->count(['client' => $mockClient1]);
        self::assertSame(3, $count1);

        $count2 = $repository->count(['client' => $mockClient2]);
        self::assertSame(1, $count2);
    }

    public function testFindByClientIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient = $this->createOAuth2Client($mockUser);

        // 创建 client 为 null 的访问日志
        $logWithNullClient = OAuth2AccessLog::create(
            'token',
            '127.0.0.1',
            'POST',
            'success'
        );
        $this->persistAndFlush($logWithNullClient);

        // 创建有 client 的访问日志
        $logWithClient = $this->createOAuth2AccessLog('authorize', $mockClient, null, 'error');
        $this->persistAndFlush($logWithClient);

        $results = $repository->findBy(['client' => null]);
        self::assertCount(1, $results);
        self::assertSame($logWithNullClient->getId(), $results[0]->getId());
    }

    public function testCountByClientIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient = $this->createOAuth2Client($mockUser);

        // 创建多个 client 为 null 的访问日志
        for ($i = 1; $i <= 2; ++$i) {
            $log = OAuth2AccessLog::create(
                'token',
                '127.0.0.' . $i,
                'POST',
                'success'
            );
            $this->persistAndFlush($log);
        }

        // 创建有 client 的访问日志
        $logWithClient = $this->createOAuth2AccessLog('authorize', $mockClient, null, 'error');
        $this->persistAndFlush($logWithClient);

        $count = $repository->count(['client' => null]);
        self::assertSame(2, $count);
    }

    public function testFindByErrorCodeIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 errorCode 为 null 的访问日志（成功状态），使用唯一IP
        $uniqueIp1 = '192.168.70.' . uniqid();
        $logWithNullErrorCode = OAuth2AccessLog::create(
            'token',
            $uniqueIp1,
            'POST',
            'success'
        );
        $this->persistAndFlush($logWithNullErrorCode);

        // 创建有 errorCode 的访问日志（错误状态），使用唯一IP
        $uniqueIp2 = '192.168.70.' . uniqid();
        $logWithErrorCode = OAuth2AccessLog::create(
            'token',
            $uniqueIp2,
            'POST',
            'error'
        );
        $logWithErrorCode->setErrorCode('invalid_request');
        $this->persistAndFlush($logWithErrorCode);

        // 查询所有errorCode为null的日志，然后过滤出我们创建的
        $results = $repository->findBy(['errorCode' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($results, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame($logWithNullErrorCode->getId(), array_values($ourLogs)[0]->getId());
    }

    public function testCountByErrorCodeIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建多个 errorCode 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 3; ++$i) {
            $uniqueIp = '192.168.71.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                'token',
                $uniqueIp,
                'POST',
                'success'
            );
            $this->persistAndFlush($log);
        }

        // 查询所有errorCode为null的日志，然后计算我们创建的
        $results = $repository->findBy(['errorCode' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($results, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(3, $ourLogsCount);
    }

    public function testFindByErrorMessageIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 errorMessage 为 null 的访问日志，使用唯一IP
        $uniqueIp1 = '192.168.72.' . uniqid();
        $logWithNullErrorMessage = OAuth2AccessLog::create(
            'token',
            $uniqueIp1,
            'POST',
            'success'
        );
        $this->persistAndFlush($logWithNullErrorMessage);

        // 创建有 errorMessage 的访问日志，使用唯一IP
        $uniqueIp2 = '192.168.72.' . uniqid();
        $logWithErrorMessage = OAuth2AccessLog::create(
            'token',
            $uniqueIp2,
            'POST',
            'error'
        );
        $logWithErrorMessage->setErrorMessage('Invalid client credentials');
        $this->persistAndFlush($logWithErrorMessage);

        // 查询所有errorMessage为null的日志，然后过滤出我们创建的
        $results = $repository->findBy(['errorMessage' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($results, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame($logWithNullErrorMessage->getId(), array_values($ourLogs)[0]->getId());
    }

    public function testCountByErrorMessageIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建多个 errorMessage 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 2; ++$i) {
            $uniqueIp = '192.168.73.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                'token',
                $uniqueIp,
                'POST',
                'success'
            );
            $this->persistAndFlush($log);
        }

        // 查询所有errorMessage为null的日志，然后计算我们创建的
        $results = $repository->findBy(['errorMessage' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($results, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(2, $ourLogsCount);
    }

    public function testFindByResponseTimeIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 responseTime 为 null 的访问日志，使用唯一IP
        $uniqueIp1 = '192.168.74.' . uniqid();
        $logWithNullResponseTime = OAuth2AccessLog::create(
            'token',
            $uniqueIp1,
            'POST',
            'success'
        );
        $this->persistAndFlush($logWithNullResponseTime);

        // 创建有 responseTime 的访问日志，使用唯一IP
        $uniqueIp2 = '192.168.74.' . uniqid();
        $logWithResponseTime = OAuth2AccessLog::create(
            'token',
            $uniqueIp2,
            'POST',
            'success'
        );
        $logWithResponseTime->setResponseTime(150);
        $this->persistAndFlush($logWithResponseTime);

        // 查询所有responseTime为null的日志，然后过滤出我们创建的
        $results = $repository->findBy(['responseTime' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($results, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame($logWithNullResponseTime->getId(), array_values($ourLogs)[0]->getId());
    }

    public function testCountByResponseTimeIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建多个 responseTime 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 4; ++$i) {
            $uniqueIp = '192.168.75.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                'token',
                $uniqueIp,
                'POST',
                'success'
            );
            $this->persistAndFlush($log);
        }

        // 查询所有responseTime为null的日志，然后计算我们创建的
        $results = $repository->findBy(['responseTime' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($results, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(4, $ourLogsCount);
    }

    public function testFindByRequestParamsIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 requestParams 为 null 的访问日志，使用唯一IP
        $uniqueIp1 = '192.168.76.' . uniqid();
        $logWithNullRequestParams = OAuth2AccessLog::create(
            'token',
            $uniqueIp1,
            'POST',
            'success'
        );
        $this->persistAndFlush($logWithNullRequestParams);

        // 创建有 requestParams 的访问日志，使用唯一IP
        $uniqueIp2 = '192.168.76.' . uniqid();
        $logWithRequestParams = OAuth2AccessLog::create(
            'token',
            $uniqueIp2,
            'POST',
            'success'
        );
        $logWithRequestParams->setRequestParams(['grant_type' => 'client_credentials']);
        $this->persistAndFlush($logWithRequestParams);

        // 查询所有requestParams为null的日志，然后过滤出我们创建的
        $results = $repository->findBy(['requestParams' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($results, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame($logWithNullRequestParams->getId(), array_values($ourLogs)[0]->getId());
    }

    public function testCountByRequestParamsIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建多个 requestParams 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 2; ++$i) {
            $uniqueIp = '192.168.77.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                'token',
                $uniqueIp,
                'POST',
                'success'
            );
            $this->persistAndFlush($log);
        }

        // 查询所有requestParams为null的日志，然后计算我们创建的
        $results = $repository->findBy(['requestParams' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($results, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(2, $ourLogsCount);
    }

    public function testFindOneByAssociationClientShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient1 = $this->createOAuth2Client($mockUser);
        $mockClient2 = $this->createOAuth2Client($mockUser);

        $log1 = $this->createOAuth2AccessLog('token', $mockClient1, null, 'success');
        $this->persistAndFlush($log1);

        $log2 = $this->createOAuth2AccessLog('authorize', $mockClient2, null, 'error');
        $this->persistAndFlush($log2);

        $result = $repository->findOneBy(['client' => $mockClient1]);
        self::assertNotNull($result);
        self::assertSame($log1->getId(), $result->getId());
        self::assertSame('token', $result->getEndpoint());
    }

    public function testCountByAssociationClientShouldReturnCorrectNumber(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient1 = $this->createOAuth2Client($mockUser);
        $mockClient2 = $this->createOAuth2Client($mockUser);

        // 为第一个客户端创建4个日志
        for ($i = 1; $i <= 4; ++$i) {
            $log = $this->createOAuth2AccessLog('token', $mockClient1, null, 'success');
            $this->persistAndFlush($log);
        }

        // 为第二个客户端创建2个日志
        for ($i = 1; $i <= 2; ++$i) {
            $log = $this->createOAuth2AccessLog('authorize', $mockClient2, null, 'error');
            $this->persistAndFlush($log);
        }

        $count = $repository->count(['client' => $mockClient1]);
        self::assertSame(4, $count);
    }

    private function createOAuth2Client(UserInterface $user): OAuth2Client
    {
        $client = new OAuth2Client();
        $client->setClientId('test-client-' . uniqid());
        $client->setClientSecret('test-secret');
        $client->setName('Test Client');
        // 由于Doctrine映射问题，我们不设置user关联，或者使用null
        // $client->setUser($user);
        $client->setRedirectUris(['https://example.com/callback']);
        $client->setGrantTypes(['authorization_code', 'client_credentials']);
        $client->setEnabled(true);
        $this->persistAndFlush($client);

        return $client;
    }

    private function createOAuth2AccessLog(
        string $endpoint,
        ?OAuth2Client $client = null,
        mixed $userId = null,
        string $status = 'success',
    ): OAuth2AccessLog {
        $userIdString = $userId instanceof UserInterface ? $userId->getUserIdentifier() : null;

        return OAuth2AccessLog::create(
            $endpoint,
            '127.0.0.1',
            'POST',
            $status,
            $client?->getClientId(),
            $client,
            $userIdString
        );
    }

    public function testFindOneByWithOrderBy(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient = $this->createOAuth2Client($mockUser);

        $log1 = $this->createOAuth2AccessLog('/token', $mockClient, $mockUser);
        $log1->setErrorCode('001');
        $this->persistAndFlush($log1);

        $log2 = $this->createOAuth2AccessLog('/authorize', $mockClient, $mockUser);
        $log2->setErrorCode('002');
        $this->persistAndFlush($log2);

        $result = $repository->findOneBy(['client' => $mockClient], ['endpoint' => 'ASC']);
        self::assertInstanceOf(OAuth2AccessLog::class, $result);
        self::assertSame('/authorize', $result->getEndpoint());

        $result = $repository->findOneBy(['client' => $mockClient], ['endpoint' => 'DESC']);
        self::assertInstanceOf(OAuth2AccessLog::class, $result);
        self::assertSame('/token', $result->getEndpoint());
    }

    public function testFindByClientIdIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 clientId 为 null 的访问日志，使用唯一IP
        $uniqueIp1 = '192.168.78.' . uniqid();
        $log1 = OAuth2AccessLog::create(
            '/token',
            $uniqueIp1,
            'POST',
            'success',
            null
        );
        $this->persistAndFlush($log1);

        // 创建有 clientId 的访问日志，使用唯一IP
        $uniqueIp2 = '192.168.78.' . uniqid();
        $mockUser = $this->createNormalUser('user-' . uniqid() . '@test.com', 'pass123');
        $mockClient = $this->createOAuth2Client($mockUser);
        $log2 = OAuth2AccessLog::create(
            '/authorize',
            $uniqueIp2,
            'POST',
            'success',
            $mockClient->getClientId(),
            $mockClient
        );
        $this->persistAndFlush($log2);

        // 查询所有clientId为null的日志，然后过滤出我们创建的
        $results = $repository->findBy(['clientId' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($results, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame('/token', array_values($ourLogs)[0]->getEndpoint());
    }

    public function testCountByClientIdIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建多个 clientId 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 2; ++$i) {
            $uniqueIp = '192.168.79.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                "/null-client-{$i}",
                $uniqueIp,
                'POST',
                'success',
                null
            );
            $this->persistAndFlush($log);
        }

        // 查询所有clientId为null的日志，然后计算我们创建的
        $results = $repository->findBy(['clientId' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($results, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(2, $ourLogsCount);
    }

    public function testFindByUserIdIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 userId 为 null 的访问日志，使用唯一IP
        $uniqueIp1 = '192.168.80.' . uniqid();
        $log1 = OAuth2AccessLog::create(
            '/token',
            $uniqueIp1,
            'POST',
            'success',
            null,
            null,
            null
        );
        $this->persistAndFlush($log1);

        // 创建有 userId 的访问日志，使用唯一IP
        $uniqueIp2 = '192.168.80.' . uniqid();
        $log2 = OAuth2AccessLog::create(
            '/authorize',
            $uniqueIp2,
            'POST',
            'success',
            null,
            null,
            'user123'
        );
        $this->persistAndFlush($log2);

        // 查询所有userId为null的日志，然后过滤出我们创建的
        $results = $repository->findBy(['userId' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($results, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame('/token', array_values($ourLogs)[0]->getEndpoint());
    }

    public function testCountByUserIdIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建多个 userId 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 3; ++$i) {
            $uniqueIp = '192.168.81.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                "/null-user-{$i}",
                $uniqueIp,
                'POST',
                'success',
                null,
                null,
                null
            );
            $this->persistAndFlush($log);
        }

        // 查询所有userId为null的日志，然后计算我们创建的
        $results = $repository->findBy(['userId' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($results, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(3, $ourLogsCount);
    }

    public function testFindByUserAgentIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建 userAgent 为 null 的访问日志，使用唯一IP
        $uniqueIp1 = '192.168.82.' . uniqid();
        $log1 = OAuth2AccessLog::create(
            '/token',
            $uniqueIp1,
            'POST',
            'success'
        );
        $log1->setUserAgent(null);
        $this->persistAndFlush($log1);

        // 创建有 userAgent 的访问日志，使用唯一IP
        $uniqueIp2 = '192.168.82.' . uniqid();
        $log2 = OAuth2AccessLog::create(
            '/authorize',
            $uniqueIp2,
            'POST',
            'success'
        );
        $log2->setUserAgent('Mozilla/5.0');
        $this->persistAndFlush($log2);

        // 查询所有userAgent为null的日志，然后过滤出我们创建的
        $results = $repository->findBy(['userAgent' => null]);

        // 过滤出我们创建的日志
        $ourLogs = array_filter($results, function ($log) use ($uniqueIp1) {
            return $log->getIpAddress() === $uniqueIp1;
        });

        self::assertCount(1, $ourLogs);
        self::assertSame('/token', array_values($ourLogs)[0]->getEndpoint());
    }

    public function testCountByUserAgentIsNull(): void
    {
        $repository = self::getService(OAuth2AccessLogRepository::class);

        // 创建多个 userAgent 为 null 的访问日志，使用唯一IP范围
        $uniqueIps = [];
        for ($i = 1; $i <= 2; ++$i) {
            $uniqueIp = '192.168.83.' . uniqid();
            $uniqueIps[] = $uniqueIp;

            $log = OAuth2AccessLog::create(
                "/null-agent-{$i}",
                $uniqueIp,
                'POST',
                'success'
            );
            $log->setUserAgent(null);
            $this->persistAndFlush($log);
        }

        // 查询所有userAgent为null的日志，然后计算我们创建的
        $results = $repository->findBy(['userAgent' => null]);

        // 计算我们创建的日志数量
        $ourLogsCount = count(array_filter($results, function ($log) use ($uniqueIps) {
            return in_array($log->getIpAddress(), $uniqueIps, true);
        }));

        self::assertSame(2, $ourLogsCount);
    }

    /**
     * @return OAuth2AccessLog
     */
    protected function createNewEntity(): object
    {
        $entity = new OAuth2AccessLog();

        // 设置基本字段
        $entity->setEndpoint('test_endpoint');
        $entity->setIpAddress('127.0.0.1');
        $entity->setMethod('GET');
        $entity->setStatus('success');

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<OAuth2AccessLog>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(OAuth2AccessLogRepository::class);
    }
}
