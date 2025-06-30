<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Tourze\OAuth2ServerBundle\Service\AttributeControllerLoader;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

class AttributeControllerLoaderTest extends TestCase
{
    private AttributeControllerLoader $loader;

    public function testExtendsLoader(): void
    {
        self::assertInstanceOf(Loader::class, $this->loader);
    }

    public function testImplementsRoutingAutoLoaderInterface(): void
    {
        self::assertInstanceOf(RoutingAutoLoaderInterface::class, $this->loader);
    }

    public function testConstructor(): void
    {
        self::assertInstanceOf(AttributeControllerLoader::class, $this->loader);
    }

    public function testSupportsReturnsFalse(): void
    {
        $result = $this->loader->supports('resource', 'type');

        self::assertFalse($result);
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $result = $this->loader->autoload();

        self::assertInstanceOf(RouteCollection::class, $result);
    }

    public function testLoadCallsAutoload(): void
    {
        $result = $this->loader->load('resource');

        self::assertInstanceOf(RouteCollection::class, $result);
    }

    protected function setUp(): void
    {
        $this->loader = new AttributeControllerLoader();
    }
}