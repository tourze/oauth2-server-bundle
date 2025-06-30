<?php

namespace Tourze\OAuth2ServerBundle\Tests\Integration\Controller\Admin\Action;

use PHPUnit\Framework\TestCase;
use Tourze\OAuth2ServerBundle\Controller\Admin\Action\RegenerateSecretController;

class RegenerateSecretControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        self::assertTrue(class_exists(RegenerateSecretController::class));
    }
}