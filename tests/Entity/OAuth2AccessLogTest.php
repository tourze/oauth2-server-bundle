<?php

namespace Tourze\OAuth2ServerBundle\Tests\Entity;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

/**
 * OAuth2AccessLog实体单元测试
 */
class OAuth2AccessLogTest extends TestCase
{
    private OAuth2Client&MockObject $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(OAuth2Client::class);
    }

    public function test_constructor_setsCreatedAtToCurrentTime(): void
    {
        $beforeCreation = new \DateTime();
        $log = new OAuth2AccessLog();
        $afterCreation = new \DateTime();
        
        $this->assertGreaterThanOrEqual($beforeCreation, $log->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $log->getCreatedAt());
    }

    public function test_setEndpoint_andGetEndpoint(): void
    {
        $log = new OAuth2AccessLog();
        $endpoint = 'token';
        
        $result = $log->setEndpoint($endpoint);
        
        $this->assertSame($log, $result);
        $this->assertSame($endpoint, $log->getEndpoint());
    }

    public function test_setClientId_andGetClientId(): void
    {
        $log = new OAuth2AccessLog();
        $clientId = 'test_client_123';
        
        $result = $log->setClientId($clientId);
        
        $this->assertSame($log, $result);
        $this->assertSame($clientId, $log->getClientId());
    }

    public function test_setClient_andGetClient(): void
    {
        $log = new OAuth2AccessLog();
        
        $result = $log->setClient($this->mockClient);
        
        $this->assertSame($log, $result);
        $this->assertSame($this->mockClient, $log->getClient());
    }

    public function test_setUserId_andGetUserId(): void
    {
        $log = new OAuth2AccessLog();
        $userId = 'user_123';
        
        $result = $log->setUserId($userId);
        
        $this->assertSame($log, $result);
        $this->assertSame($userId, $log->getUserId());
    }

    public function test_setIpAddress_andGetIpAddress(): void
    {
        $log = new OAuth2AccessLog();
        $ipAddress = '192.168.1.100';
        
        $result = $log->setIpAddress($ipAddress);
        
        $this->assertSame($log, $result);
        $this->assertSame($ipAddress, $log->getIpAddress());
    }

    public function test_setUserAgent_andGetUserAgent(): void
    {
        $log = new OAuth2AccessLog();
        $userAgent = 'Mozilla/5.0 (compatible; Test/1.0)';
        
        $result = $log->setUserAgent($userAgent);
        
        $this->assertSame($log, $result);
        $this->assertSame($userAgent, $log->getUserAgent());
    }

    public function test_setMethod_andGetMethod(): void
    {
        $log = new OAuth2AccessLog();
        $method = 'POST';
        
        $result = $log->setMethod($method);
        
        $this->assertSame($log, $result);
        $this->assertSame($method, $log->getMethod());
    }

    public function test_setRequestParams_andGetRequestParams(): void
    {
        $log = new OAuth2AccessLog();
        $params = ['grant_type' => 'client_credentials', 'scope' => 'read'];
        
        $result = $log->setRequestParams($params);
        
        $this->assertSame($log, $result);
        $this->assertSame($params, $log->getRequestParams());
    }

    public function test_setStatus_andGetStatus(): void
    {
        $log = new OAuth2AccessLog();
        $status = 'success';
        
        $result = $log->setStatus($status);
        
        $this->assertSame($log, $result);
        $this->assertSame($status, $log->getStatus());
    }

    public function test_setErrorCode_andGetErrorCode(): void
    {
        $log = new OAuth2AccessLog();
        $errorCode = 'invalid_client';
        
        $result = $log->setErrorCode($errorCode);
        
        $this->assertSame($log, $result);
        $this->assertSame($errorCode, $log->getErrorCode());
    }

    public function test_setErrorMessage_andGetErrorMessage(): void
    {
        $log = new OAuth2AccessLog();
        $errorMessage = 'Client authentication failed';
        
        $result = $log->setErrorMessage($errorMessage);
        
        $this->assertSame($log, $result);
        $this->assertSame($errorMessage, $log->getErrorMessage());
    }

    public function test_setResponseTime_andGetResponseTime(): void
    {
        $log = new OAuth2AccessLog();
        $responseTime = 150;
        
        $result = $log->setResponseTime($responseTime);
        
        $this->assertSame($log, $result);
        $this->assertSame($responseTime, $log->getResponseTime());
    }

    public function test_setCreatedAt_andGetCreatedAt(): void
    {
        $log = new OAuth2AccessLog();
        $dateTime = new \DateTime('2023-01-01 12:00:00');
        
        $result = $log->setCreatedAt($dateTime);
        
        $this->assertSame($log, $result);
        $this->assertSame($dateTime, $log->getCreatedAt());
    }

    public function test_isSuccess_returnsTrueForSuccessStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('success');
        
        $this->assertTrue($log->isSuccess());
    }

    public function test_isSuccess_returnsFalseForNonSuccessStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('error');
        
        $this->assertFalse($log->isSuccess());
    }

    public function test_isError_returnsTrueForErrorStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('error');
        
        $this->assertTrue($log->isError());
    }

    public function test_isError_returnsFalseForNonErrorStatus(): void
    {
        $log = new OAuth2AccessLog();
        $log->setStatus('success');
        
        $this->assertFalse($log->isError());
    }

    public function test_getFormattedResponseTime_returnsFormattedTime(): void
    {
        $log = new OAuth2AccessLog();
        $log->setResponseTime(250);
        
        $this->assertSame('250ms', $log->getFormattedResponseTime());
    }

    public function test_getFormattedResponseTime_returnsNAForNullTime(): void
    {
        $log = new OAuth2AccessLog();
        $log->setResponseTime(null);
        
        $this->assertSame('N/A', $log->getFormattedResponseTime());
    }

    public function test_create_withAllParameters(): void
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
        
        $this->assertInstanceOf(OAuth2AccessLog::class, $log);
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

    public function test_create_withMinimalParameters(): void
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
        
        $this->assertInstanceOf(OAuth2AccessLog::class, $log);
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

    public function test_create_withErrorInformation(): void
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

    public function test_create_setsCreatedAtToCurrentTime(): void
    {
        $beforeCreation = new \DateTime();
        
        $log = OAuth2AccessLog::create(
            'test',
            '127.0.0.1',
            'GET',
            'success'
        );
        
        $afterCreation = new \DateTime();
        
        $this->assertGreaterThanOrEqual($beforeCreation, $log->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $log->getCreatedAt());
    }
} 