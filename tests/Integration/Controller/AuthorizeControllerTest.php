<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Tourze\OAuth2ServerBundle\Controller\AuthorizeController;
use Tourze\OAuth2ServerBundle\Service\AccessLogService;
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

class AuthorizeControllerTest extends TestCase
{
    private AuthorizationService&MockObject $authorizationService;
    private AccessLogService&MockObject $accessLogService;
    private AuthorizeController $controller;

    public function testExtendsAbstractController(): void
    {
        self::assertInstanceOf(AbstractController::class, $this->controller);
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(AuthorizeController::class, $this->controller);
    }

    protected function setUp(): void
    {
        $this->authorizationService = $this->createMock(AuthorizationService::class);
        $this->accessLogService = $this->createMock(AccessLogService::class);
        $this->controller = new AuthorizeController(
            $this->authorizationService,
            $this->accessLogService
        );
    }
}