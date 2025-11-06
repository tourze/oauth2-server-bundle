<?php

namespace Tourze\OAuth2ServerBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\AccessTokenBundle\AccessTokenBundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;

class OAuth2ServerBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            AccessTokenBundle::class => ['all' => true],
            RoutingAutoLoaderBundle::class => ['all' => true],
        ];
    }
}
