<?php

namespace Tourze\OAuth2ServerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\AccessTokenContracts\TokenServiceInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Exception\OAuth2Exception;
use Tourze\OAuth2ServerBundle\Repository\AuthorizationCodeRepository;
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AuthorizationService::class)]
#[RunTestsInSeparateProcesses]
final class AuthorizationServiceTest extends AbstractIntegrationTestCase
{
    private OAuth2ClientService&MockObject $clientService;

    private TokenServiceInterface&MockObject $accessTokenService;

    private AuthorizationCodeRepository&MockObject $authCodeRepository;

    private AuthorizationService $authorizationService;

    public function testServiceCanBeCreated(): void
    {
        self::assertTrue(class_exists(AuthorizationService::class));
    }

    public function testHandleClientCredentialsGrantThrowsExceptionForInvalidClient(): void
    {
        $this->clientService
            ->expects(self::once())
            ->method('validateClient')
            ->with('invalid_client', 'secret')
            ->willReturn(null)
        ;

        $this->expectException(OAuth2Exception::class);
        $this->expectExceptionMessage('Invalid client credentials');

        $this->authorizationService->handleClientCredentialsGrant('invalid_client', 'secret');
    }

    public function testCleanupExpiredAuthorizationCodes(): void
    {
        $this->authCodeRepository
            ->expects(self::once())
            ->method('removeExpiredCodes')
            ->willReturn(5)
        ;

        $result = $this->authorizationService->cleanupExpiredAuthorizationCodes();

        self::assertEquals(5, $result);
    }

    public function testExchangeAuthorizationCode(): void
    {
        $this->authCodeRepository
            ->expects(self::once())
            ->method('findValidByCode')
            ->with('test_code')
            ->willReturn(null)
        ;

        $this->expectException(OAuth2Exception::class);
        $this->expectExceptionMessage('Invalid authorization code');

        $this->authorizationService->exchangeAuthorizationCode(
            'test_code',
            'client_id',
            'client_secret',
            'https://example.com/callback'
        );
    }

    public function testGenerateAuthorizationCode(): void
    {
        // 使用具体类是必要的，因为：
        // 1) OAuth2Client是实体类，包含特定的业务逻辑和验证方法
        // 2) 服务方法需要调用实体的特定方法（如重定向URI验证）
        // 3) 没有合适的接口可以替代，且测试需要验证与实际实体的交互
        $client = $this->createMock(OAuth2Client::class);
        $user = $this->createMock(UserInterface::class);

        $this->clientService
            ->expects(self::once())
            ->method('validateRedirectUri')
            ->with($client, 'https://example.com/callback')
            ->willReturn(false)
        ;

        $this->expectException(OAuth2Exception::class);
        $this->expectExceptionMessage('Invalid redirect URI');

        $this->authorizationService->generateAuthorizationCode(
            $client,
            $user,
            'https://example.com/callback'
        );
    }

    public function testValidateAuthorizationRequest(): void
    {
        $this->clientService
            ->expects(self::once())
            ->method('validateClient')
            ->with('invalid_client')
            ->willReturn(null)
        ;

        $this->expectException(OAuth2Exception::class);
        $this->expectExceptionMessage('Invalid client');

        $this->authorizationService->validateAuthorizationRequest(
            'invalid_client',
            'code',
            'https://example.com/callback'
        );
    }

    protected function onSetUp(): void
    {
        // 创建Mock依赖
        $this->clientService = $this->createMock(OAuth2ClientService::class);
        $this->accessTokenService = $this->createMock(TokenServiceInterface::class);
        $this->authCodeRepository = $this->createMock(AuthorizationCodeRepository::class);

        // 将Mock服务注入容器
        self::getContainer()->set(OAuth2ClientService::class, $this->clientService);
        self::getContainer()->set(TokenServiceInterface::class, $this->accessTokenService);
        self::getContainer()->set(AuthorizationCodeRepository::class, $this->authCodeRepository);

        // 从容器中获取服务实例
        $this->authorizationService = self::getService(AuthorizationService::class);
    }
}
