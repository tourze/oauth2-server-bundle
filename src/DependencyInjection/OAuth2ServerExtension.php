<?php

namespace Tourze\OAuth2ServerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class OAuth2ServerExtension extends AutoExtension implements PrependExtensionInterface
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function prepend(ContainerBuilder $container): void
    {
        // 预配置方法 - 目前无特殊配置需求
    }
}
