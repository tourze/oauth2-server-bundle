<?php

namespace Tourze\OAuth2ServerBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\OAuth2ServerBundle\DependencyInjection\OAuth2ServerExtension;

class OAuth2ServerExtensionTest extends TestCase
{
    public function testInstanceOfExtension(): void
    {
        $extension = new OAuth2ServerExtension();
        
        self::assertInstanceOf(Extension::class, $extension);
    }

    public function testLoad(): void
    {
        $extension = new OAuth2ServerExtension();
        $container = new ContainerBuilder();
        
        $extension->load([], $container);
        
        self::assertTrue($container->hasAlias('tourze_oauth2_server.service.oauth2_client_service'));
    }
}