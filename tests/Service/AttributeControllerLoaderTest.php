<?php

namespace Tourze\OAuth2ServerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OAuth2ServerBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    public function testServiceCanBeCreated(): void
    {
        self::assertTrue(class_exists(AttributeControllerLoader::class));
    }

    public function testSupportsReturnsFalse(): void
    {
        $result = $this->loader->supports('resource', 'type');

        self::assertFalse($result);
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $result = $this->loader->autoload();

        // 4个控制器: AuthorizeController, TokenController, RegenerateSecretController, ToggleStatusController
        self::assertCount(4, $result->all());
    }

    public function testLoadCallsAutoload(): void
    {
        // Since load() delegates to autoload(), we verify they return the same result
        $loadResult = $this->loader->load('resource');
        $autoloadResult = $this->loader->autoload();

        // Verify both methods return RouteCollections with the same routes
        self::assertEquals($autoloadResult->all(), $loadResult->all());
    }

    protected function onSetUp(): void
    {
        // 从容器获取服务实例
        $this->loader = self::getService(AttributeControllerLoader::class);
    }
}
