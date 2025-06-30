<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Service;

use AccessTokenBundle\Service\AccessTokenService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Repository\AuthorizationCodeRepository;
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;

class AuthorizationServiceTest extends TestCase
{
    private OAuth2ClientService&MockObject $clientService;
    private AccessTokenService&MockObject $accessTokenService;
    private AuthorizationCodeRepository&MockObject $authCodeRepository;
    private AuthorizationService $authorizationService;

    public function testConstructor(): void
    {
        self::assertInstanceOf(AuthorizationService::class, $this->authorizationService);
    }

    public function testHandleClientCredentialsGrantThrowsExceptionForInvalidClient(): void
    {
        $this->clientService
            ->expects(self::once())
            ->method('validateClient')
            ->with('invalid_client', 'secret')
            ->willReturn(null);

        $this->expectException(\Tourze\OAuth2ServerBundle\Exception\OAuth2Exception::class);
        $this->expectExceptionMessage('Invalid client credentials');

        $this->authorizationService->handleClientCredentialsGrant('invalid_client', 'secret');
    }

    protected function setUp(): void
    {
        $this->clientService = $this->createMock(OAuth2ClientService::class);
        $this->accessTokenService = $this->createMock(AccessTokenService::class);
        $this->authCodeRepository = $this->createMock(AuthorizationCodeRepository::class);
        $this->authorizationService = new AuthorizationService(
            $this->clientService,
            $this->accessTokenService,
            $this->authCodeRepository
        );
    }
}