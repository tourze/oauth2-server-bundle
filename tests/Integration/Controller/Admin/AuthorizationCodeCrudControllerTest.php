<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Controller\Admin\AuthorizationCodeCrudController;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;

class AuthorizationCodeCrudControllerTest extends TestCase
{
    private AuthorizationCodeCrudController $controller;

    public function testExtendsAbstractCrudController(): void
    {
        self::assertInstanceOf(AbstractCrudController::class, $this->controller);
    }

    public function testGetEntityFqcn(): void
    {
        $fqcn = AuthorizationCodeCrudController::getEntityFqcn();

        self::assertEquals(AuthorizationCode::class, $fqcn);
    }

    protected function setUp(): void
    {
        $this->controller = new AuthorizationCodeCrudController();
    }
}