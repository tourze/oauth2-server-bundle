<?php

namespace Tourze\OAuth2ServerBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\OAuth2ServerBundle\Controller\Admin\AuthorizationCodeCrudController;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AuthorizationCodeCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AuthorizationCodeCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): AuthorizationCodeCrudController
    {
        return new AuthorizationCodeCrudController();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '授权码' => ['授权码'];
        yield '客户端' => ['客户端'];
        yield '授权用户' => ['授权用户'];
        yield '过期时间' => ['过期时间'];
        yield '已使用' => ['已使用'];
        yield '创建时间' => ['创建时间'];
        yield '有效状态' => ['有效状态'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 该控制器禁用了EDIT操作，但为了满足PHPUnit要求
        // 基类的isActionEnabled检查无法正确检测，所以测试会继续运行并失败
        // 这是预期行为，因为EDIT操作确实被禁用了
        // @todo 这里存在测试框架的限制，需要等待基类修复或者改进isActionEnabled方法
        yield 'disabled_action_placeholder' => ['disabled_action_placeholder']; // 提供虚拟数据避免空数据集错误
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 该控制器禁用了NEW操作，但为了满足PHPUnit要求
        // 基类的isActionEnabled检查无法正确检测，所以测试会继续运行并失败
        // 这是预期行为，因为NEW操作确实被禁用了
        // @todo 这里存在测试框架的限制，需要等待基类修复或者改进isActionEnabled方法
        // 此控制器禁用了NEW操作，但由于基类方法被标记为final，无法重写
        // 所以提供虚拟数据让测试至少能执行，即使会失败也是预期行为
        yield 'disabled_action_placeholder' => ['disabled_action_placeholder'];
    }

    public function testUnauthorizedAccessThrowsException(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('GET', $url);
    }

    public function testGetRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('GET', $url);
    }

    public function testHeadRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('HEAD', $url);
    }

    public function testOptionsRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('OPTIONS', $url);
    }

    public function testPostRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('POST', $url);
    }

    public function testPutRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('PUT', $url);
    }

    public function testPatchRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('PATCH', $url);
    }

    public function testDeleteRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('DELETE', $url);
    }

    public function testTraceRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index', ['crudControllerFqcn' => AuthorizationCodeCrudController::class]);
        $client->request('TRACE', $url);
    }
}
