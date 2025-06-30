<?php

namespace Tourze\OAuth2ServerBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\OAuth2ServerBundle\OAuth2ServerBundle;

class OAuth2ServerBundleTest extends TestCase
{
    public function testInstanceOfBundle(): void
    {
        $bundle = new OAuth2ServerBundle();
        
        self::assertInstanceOf(Bundle::class, $bundle);
    }
}