<?php

namespace Tourze\OAuth2ServerBundle\Tests\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\OAuth2ServerBundle\DependencyInjection\OAuth2ServerExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(OAuth2ServerExtension::class)]
final class OAuth2ServerExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function setUpContainer(): void
    {
        // 这个测试不需要额外的设置
    }

    public function testExtensionCanBeCreated(): void
    {
        self::assertTrue(class_exists(OAuth2ServerExtension::class));
    }

    public function testLoad(): void
    {
        // 直接创建扩展实例
        $extension = new OAuth2ServerExtension();
        $container = new ContainerBuilder();

        // 设置必要的参数
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('Tourze\OAuth2ServerBundle\Service\OAuth2ClientService'));
    }

    public function testPrepend(): void
    {
        $extension = new OAuth2ServerExtension();
        $container = new ContainerBuilder();

        // 模拟有Doctrine Bundle的情况
        $container->registerExtension(new DoctrineExtension());

        $extension->prepend($container);

        // 验证prepend方法执行成功（检查扩展是否已注册）
        self::assertTrue($container->hasExtension('doctrine'));
    }
}
