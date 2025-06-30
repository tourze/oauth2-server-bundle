<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2AccessLogCrudController;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;

class OAuth2AccessLogCrudControllerTest extends TestCase
{
    private OAuth2AccessLogCrudController $controller;

    public function testExtendsAbstractCrudController(): void
    {
        self::assertInstanceOf(AbstractCrudController::class, $this->controller);
    }

    public function testGetEntityFqcn(): void
    {
        $fqcn = OAuth2AccessLogCrudController::getEntityFqcn();

        self::assertEquals(OAuth2AccessLog::class, $fqcn);
    }

    protected function setUp(): void
    {
        $this->controller = new OAuth2AccessLogCrudController();
    }
}