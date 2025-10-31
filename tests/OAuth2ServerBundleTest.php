<?php

declare(strict_types=1);

namespace Oauth2ServerBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OAuth2ServerBundle\OAuth2ServerBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(OAuth2ServerBundle::class)]
#[RunTestsInSeparateProcesses]
final class OAuth2ServerBundleTest extends AbstractBundleTestCase
{
}
