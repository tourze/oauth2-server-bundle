<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Tourze\OAuth2ServerBundle\Controller\TokenController;
use Tourze\OAuth2ServerBundle\Service\AccessLogService;
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

class TokenControllerTest extends TestCase
{
    private AuthorizationService&MockObject $authorizationService;
    private AccessLogService&MockObject $accessLogService;
    private TokenController $controller;

    public function testExtendsAbstractController(): void
    {
        self::assertInstanceOf(AbstractController::class, $this->controller);
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(TokenController::class, $this->controller);
    }

    public function testInvokeWithUnsupportedGrantType(): void
    {
        $request = new Request();
        $request->request->set('grant_type', 'unsupported_grant');

        $response = $this->controller->__invoke($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertEquals('unsupported_grant_type', $data['error']);
    }

    public function testInvokeWithMissingGrantType(): void
    {
        $request = new Request();

        $response = $this->controller->__invoke($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        self::assertEquals('unsupported_grant_type', $data['error']);
    }

    protected function setUp(): void
    {
        $this->authorizationService = $this->createMock(AuthorizationService::class);
        $this->accessLogService = $this->createMock(AccessLogService::class);
        $this->controller = new TokenController(
            $this->authorizationService,
            $this->accessLogService
        );
    }
}