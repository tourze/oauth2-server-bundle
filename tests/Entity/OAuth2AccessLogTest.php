<?php

declare(strict_types=1);

namespace Tourze\OAuth2ServerBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * OAuth2AccessLog实体测试类
 *
 * @internal
 */
#[CoversClass(OAuth2AccessLog::class)]
final class OAuth2AccessLogTest extends AbstractEntityTestCase
{
    private OAuth2Client&MockObject $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock具体类是必要的，因为：
        // 1) OAuth2Client实体类包含业务逻辑方法，需要验证特定的行为
        // 2) 没有合适的接口可以替代
        // 3) 单元测试需要控制实体的状态和行为
        $this->mockClient = $this->createMock(OAuth2Client::class);
    }

    protected function setUpContainer(): void
    {
        // 这个测试不需要额外的容器设置
    }

    protected function createEntity(): OAuth2AccessLog
    {
        return new OAuth2AccessLog();
    }

    /**
     * 提供要测试的属性及其示例值
     */
    /**
     * @return array<int, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            ['endpoint', 'token'],
            ['clientId', 'test_client_123'],
            ['client', null], // 关联实体，设为null
            ['userId', 'user_123'],
            ['ipAddress', '192.168.1.100'],
            ['userAgent', 'Mozilla/5.0 (compatible; Test/1.0)'],
            ['method', 'POST'],
            ['requestParams', ['grant_type' => 'client_credentials', 'scope' => 'read']],
            ['status', 'success'],
            ['errorCode', 'invalid_client'],
            ['errorMessage', 'Client authentication failed'],
            ['responseTime', 150],
            ['createTime', new \DateTimeImmutable()],
        ];
    }

    // Getter/setter 测试由 AbstractEntityTest 自动提供

    public function testConstructorSetsCreateTimeToCurrentTime(): void
    {
        $beforeCreation = new \DateTimeImmutable();
        $log = new OAuth2AccessLog();
        $afterCreation = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($beforeCreation, $log->getCreateTime());
        $this->assertLessThanOrEqual($afterCreation, $log->getCreateTime());
    }

    public function testIsSuccessReturnsTrueForSuccessStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('success');

        $this->assertTrue($log->isSuccess());
    }

    public function testIsSuccessReturnsFalseForNonSuccessStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('error');

        $this->assertFalse($log->isSuccess());
    }

    public function testIsErrorReturnsTrueForErrorStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('error');

        $this->assertTrue($log->isError());
    }

    public function testIsErrorReturnsFalseForNonErrorStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('success');

        $this->assertFalse($log->isError());
    }

    public function testGetFormattedResponseTimeReturnsFormattedTime(): void
    {
        $log = new OAuth2AccessLog();
        $log->setResponseTime(250);

        $this->assertSame('250ms', $log->getFormattedResponseTime());
    }

    public function testGetFormattedResponseTimeReturnsNAForNullTime(): void
    {
        $log = new OAuth2AccessLog();
        $log->setResponseTime(null);

        $this->assertSame('N/A', $log->getFormattedResponseTime());
    }

    public function testCreateWithAllParameters(): void
    {
        $endpoint = 'token';
        $ipAddress = '192.168.1.100';
        $method = 'POST';
        $status = 'success';
        $clientId = 'test_client';
        $userId = 'user_123';
        $userAgent = 'Test Agent';
        $requestParams = ['grant_type' => 'authorization_code'];
        $errorCode = null;
        $errorMessage = null;
        $responseTime = 300;

        $log = OAuth2AccessLog::create(
            $endpoint,
            $ipAddress,
            $method,
            $status,
            $clientId,
            $this->mockClient,
            $userId,
            $userAgent,
            $requestParams,
            $errorCode,
            $errorMessage,
            $responseTime
        );

        $this->assertSame($endpoint, $log->getEndpoint());
        $this->assertSame($ipAddress, $log->getIpAddress());
        $this->assertSame($method, $log->getMethod());
        $this->assertSame($status, $log->getStatus());
        $this->assertSame($clientId, $log->getClientId());
        $this->assertSame($this->mockClient, $log->getClient());
        $this->assertSame($userId, $log->getUserId());
        $this->assertSame($userAgent, $log->getUserAgent());
        $this->assertSame($requestParams, $log->getRequestParams());
        $this->assertNull($log->getErrorCode());
        $this->assertNull($log->getErrorMessage());
        $this->assertSame($responseTime, $log->getResponseTime());
    }

    public function testCreateWithMinimalParameters(): void
    {
        $endpoint = 'authorize';
        $ipAddress = '10.0.0.1';
        $method = 'GET';
        $status = 'error';

        $log = OAuth2AccessLog::create(
            $endpoint,
            $ipAddress,
            $method,
            $status
        );

        $this->assertSame($endpoint, $log->getEndpoint());
        $this->assertSame($ipAddress, $log->getIpAddress());
        $this->assertSame($method, $log->getMethod());
        $this->assertSame($status, $log->getStatus());
        $this->assertNull($log->getClientId());
        $this->assertNull($log->getClient());
        $this->assertNull($log->getUserId());
        $this->assertNull($log->getUserAgent());
        $this->assertNull($log->getRequestParams());
        $this->assertNull($log->getErrorCode());
        $this->assertNull($log->getErrorMessage());
        $this->assertNull($log->getResponseTime());
    }

    public function testCreateWithErrorInformation(): void
    {
        $endpoint = 'token';
        $ipAddress = '127.0.0.1';
        $method = 'POST';
        $status = 'error';
        $errorCode = 'invalid_grant';
        $errorMessage = 'Authorization code has expired';

        $log = OAuth2AccessLog::create(
            $endpoint,
            $ipAddress,
            $method,
            $status,
            null,
            null,
            null,
            null,
            null,
            $errorCode,
            $errorMessage
        );

        $this->assertSame($errorCode, $log->getErrorCode());
        $this->assertSame($errorMessage, $log->getErrorMessage());
        $this->assertTrue($log->isError());
    }

    public function testCreateSetsCreateTimeToCurrentTime(): void
    {
        $beforeCreation = new \DateTimeImmutable();

        $log = OAuth2AccessLog::create(
            'test',
            '127.0.0.1',
            'GET',
            'success'
        );

        $afterCreation = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($beforeCreation, $log->getCreateTime());
        $this->assertLessThanOrEqual($afterCreation, $log->getCreateTime());
    }
}
