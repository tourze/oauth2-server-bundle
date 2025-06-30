<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\OAuth2ServerBundle\Service\AdminMenu;

class AdminMenuTest extends TestCase
{
    private LinkGeneratorInterface&MockObject $linkGenerator;
    private AdminMenu $adminMenu;

    public function testImplementsMenuProviderInterface(): void
    {
        self::assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }

    protected function setUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $this->adminMenu = new AdminMenu($this->linkGenerator);
    }
}