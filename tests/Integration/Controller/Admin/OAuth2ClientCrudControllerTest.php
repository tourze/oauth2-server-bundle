<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2ClientCrudController;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

class OAuth2ClientCrudControllerTest extends TestCase
{
    private OAuth2ClientCrudController $controller;

    public function testExtendsAbstractCrudController(): void
    {
        self::assertInstanceOf(AbstractCrudController::class, $this->controller);
    }

    public function testGetEntityFqcn(): void
    {
        $fqcn = OAuth2ClientCrudController::getEntityFqcn();

        self::assertEquals(OAuth2Client::class, $fqcn);
    }

    protected function setUp(): void
    {
        $this->controller = new OAuth2ClientCrudController();
    }
}