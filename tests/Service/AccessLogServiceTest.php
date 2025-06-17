<?php

namespace Tourze\OAuth2ServerBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Repository\OAuth2AccessLogRepository;
use Tourze\OAuth2ServerBundle\Service\AccessLogService;

/**
 * AccessLogService单元测试
 */
class AccessLogServiceTest extends TestCase
{
    private OAuth2AccessLogRepository&MockObject $mockRepository;
    private OAuth2Client&MockObject $mockClient;
    private UserInterface&MockObject $mockUser;
    private AccessLogService $accessLogService;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(OAuth2AccessLogRepository::class);
        $this->mockClient = $this->createMock(OAuth2Client::class);
        $this->mockUser = $this->createMock(UserInterface::class);
        
        $this->mockClient->method('getClientId')->willReturn('test_client');
        $this->mockUser->method('getUserIdentifier')->willReturn('test@example.com');
        
        $this->accessLogService = new AccessLogService($this->mockRepository);
    }

    public function test_logSuccess_createsAndSavesSuccessLog(): void
    {
        $endpoint = 'token';
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $responseTime = 150;

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(OAuth2AccessLog::class));

        $result = $this->accessLogService->logSuccess($endpoint, $request, $this->mockClient, $this->mockUser, $responseTime);

        $this->assertInstanceOf(OAuth2AccessLog::class, $result);
    }

    public function test_logError_createsAndSavesErrorLog(): void
    {
        $endpoint = 'authorize';
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $errorCode = 'invalid_client';
        $errorMessage = 'Client authentication failed';
        $responseTime = 50;

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(OAuth2AccessLog::class));

        $result = $this->accessLogService->logError($endpoint, $request, $errorCode, $errorMessage, $this->mockClient, $this->mockUser, $responseTime);

        $this->assertInstanceOf(OAuth2AccessLog::class, $result);
    }

    public function test_logBatch_callsRepositorySaveBatch(): void
    {
        $logs = [
            $this->createMock(OAuth2AccessLog::class),
            $this->createMock(OAuth2AccessLog::class),
        ];

        $this->mockRepository->expects($this->once())
            ->method('saveBatch')
            ->with($logs);

        $this->accessLogService->logBatch($logs);
    }

    public function test_getEndpointStats_returnsFormattedStats(): void
    {
        $endpoint = 'token';
        $from = new \DateTime('-1 hour');
        $to = new \DateTime();
        $totalCount = 100;
        $avgResponseTime = 120.5;

        $this->mockRepository->method('getAccessCountByEndpoint')
            ->with($endpoint, $from, $to)
            ->willReturn($totalCount);
        
        $this->mockRepository->method('getAverageResponseTime')
            ->with($endpoint, $from, $to)
            ->willReturn($avgResponseTime);

        $result = $this->accessLogService->getEndpointStats($endpoint, $from, $to);

        $this->assertSame($endpoint, $result['endpoint']);
        $this->assertSame($totalCount, $result['total_count']);
        $this->assertSame($avgResponseTime, $result['average_response_time']);
        $this->assertArrayHasKey('period', $result);
    }

    public function test_getClientStats_returnsFormattedStats(): void
    {
        $from = new \DateTime('-1 day');
        $to = new \DateTime();
        $totalCount = 250;

        $this->mockRepository->method('getAccessCountByClient')
            ->with($this->mockClient, $from, $to)
            ->willReturn($totalCount);

        $this->mockClient->method('getName')->willReturn('Test Client');

        $result = $this->accessLogService->getClientStats($this->mockClient, $from, $to);

        $this->assertSame('test_client', $result['client_id']);
        $this->assertSame('Test Client', $result['client_name']);
        $this->assertSame($totalCount, $result['total_count']);
        $this->assertArrayHasKey('period', $result);
    }

    public function test_isSuspiciousIp_returnsTrueForHighTrafficIp(): void
    {
        $ipAddress = '192.168.1.100';
        $threshold = 100;
        $count = 150;

        $this->mockRepository->method('getAccessCountByIp')
            ->willReturn($count);

        $result = $this->accessLogService->isSuspiciousIp($ipAddress, $threshold);

        $this->assertTrue($result);
    }

    public function test_isSuspiciousIp_returnsFalseForNormalTrafficIp(): void
    {
        $ipAddress = '192.168.1.100';
        $threshold = 100;
        $count = 50;

        $this->mockRepository->method('getAccessCountByIp')
            ->willReturn($count);

        $result = $this->accessLogService->isSuspiciousIp($ipAddress, $threshold);

        $this->assertFalse($result);
    }

    public function test_getSuspiciousIps_returnsRepositoryResult(): void
    {
        $threshold = 200;
        $from = new \DateTime('-1 hour');
        $expectedIps = [
            ['ip_address' => '192.168.1.100', 'access_count' => 300],
            ['ip_address' => '10.0.0.1', 'access_count' => 250],
        ];

        $this->mockRepository->method('getSuspiciousIps')
            ->with($threshold, $from)
            ->willReturn($expectedIps);

        $result = $this->accessLogService->getSuspiciousIps($threshold, $from);

        $this->assertSame($expectedIps, $result);
    }

    public function test_getErrorLogs_returnsRepositoryResult(): void
    {
        $limit = 50;
        $from = new \DateTime('-1 day');
        $expectedLogs = [
            $this->createMock(OAuth2AccessLog::class),
            $this->createMock(OAuth2AccessLog::class),
        ];

        $this->mockRepository->method('getErrorLogs')
            ->with($limit, $from)
            ->willReturn($expectedLogs);

        $result = $this->accessLogService->getErrorLogs($limit, $from);

        $this->assertSame($expectedLogs, $result);
    }

    public function test_getPopularEndpoints_returnsRepositoryResult(): void
    {
        $limit = 10;
        $from = new \DateTime('-1 week');
        $to = new \DateTime();
        $expectedStats = [
            ['endpoint' => 'token', 'access_count' => 1000],
            ['endpoint' => 'authorize', 'access_count' => 800],
        ];

        $this->mockRepository->method('getPopularEndpoints')
            ->with($limit, $from, $to)
            ->willReturn($expectedStats);

        $result = $this->accessLogService->getPopularEndpoints($limit, $from, $to);

        $this->assertSame($expectedStats, $result);
    }

    public function test_getPopularClients_returnsRepositoryResult(): void
    {
        $limit = 5;
        $from = new \DateTime('-1 month');
        $to = new \DateTime();
        $expectedStats = [
            ['name' => 'Client A', 'client_id' => 'client_a', 'access_count' => 5000],
            ['name' => 'Client B', 'client_id' => 'client_b', 'access_count' => 3000],
        ];

        $this->mockRepository->method('getPopularClients')
            ->with($limit, $from, $to)
            ->willReturn($expectedStats);

        $result = $this->accessLogService->getPopularClients($limit, $from, $to);

        $this->assertSame($expectedStats, $result);
    }

    public function test_getDailyStats_returnsRepositoryResult(): void
    {
        $from = new \DateTime('-30 days');
        $to = new \DateTime();
        $expectedStats = [
            ['date' => '2023-01-01', 'total_count' => 100, 'success_count' => 90, 'error_count' => 10],
            ['date' => '2023-01-02', 'total_count' => 120, 'success_count' => 115, 'error_count' => 5],
        ];

        $this->mockRepository->method('getDailyStats')
            ->with($from, $to)
            ->willReturn($expectedStats);

        $result = $this->accessLogService->getDailyStats($from, $to);

        $this->assertSame($expectedStats, $result);
    }

    public function test_cleanupOldLogs_returnsDeletedCount(): void
    {
        $daysToKeep = 90;
        $deletedCount = 500;

        $this->mockRepository->method('cleanupOldLogs')
            ->with($this->isInstanceOf(\DateTime::class))
            ->willReturn($deletedCount);

        $result = $this->accessLogService->cleanupOldLogs($daysToKeep);

        $this->assertSame($deletedCount, $result);
    }

    public function test_logAsync_createsAndSavesLog(): void
    {
        $endpoint = 'token';
        $logData = [
            'ip_address' => '192.168.1.100',
            'method' => 'POST',
            'client_id' => 'test_client',
            'user_id' => 'user_123',
            'user_agent' => 'Test Agent',
            'request_params' => ['grant_type' => 'client_credentials'],
            'response_time' => 200,
        ];
        $status = 'success';

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(OAuth2AccessLog::class));

        $this->accessLogService->logAsync($endpoint, $logData, $status);
    }

    public function test_logAsync_withErrorInformation(): void
    {
        $endpoint = 'authorize';
        $logData = [
            'ip_address' => '127.0.0.1',
            'method' => 'GET',
        ];
        $status = 'error';
        $errorCode = 'access_denied';
        $errorMessage = 'User denied authorization';

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(OAuth2AccessLog::class));

        $this->accessLogService->logAsync($endpoint, $logData, $status, $errorCode, $errorMessage);
    }
} 