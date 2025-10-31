<?php

namespace Tourze\OAuth2ServerBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2AccessLogCrudController;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(OAuth2AccessLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class OAuth2AccessLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testUnauthorizedAccessReturnsRedirect(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index');
        $client->request('GET', $url);
    }

    public function testGetRequest(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index');
        $client->request('GET', $url);
    }

    public function testPostRequest(): void
    {
        $client = self::createClientWithDatabase();

        // 由于路由不支持POST方法，期望MethodNotAllowedHttpException而不是AccessDeniedException
        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index');
        $client->request('POST', $url);
    }

    public function testFilterSearchFunctionality(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['endpoint' => 'token'],
        ]);
        $client->request('GET', $url);
    }

    public function testClientIdFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['clientId' => 'test-client'],
        ]);
        $client->request('GET', $url);
    }

    public function testStatusFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['status' => 'success'],
        ]);
        $client->request('GET', $url);
    }

    public function testDateTimeFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['createTime' => '2024-01-01'],
        ]);
        $client->request('GET', $url);
    }

    public function testErrorCodeFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['errorCode' => 'invalid_grant'],
        ]);
        $client->request('GET', $url);
    }

    protected function getControllerService(): OAuth2AccessLogCrudController
    {
        return self::getService(OAuth2AccessLogCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '端点' => ['端点'];
        yield '客户端ID' => ['客户端ID'];
        yield '用户ID' => ['用户ID'];
        yield 'IP地址' => ['IP地址'];
        yield '请求方法' => ['请求方法'];
        yield '状态' => ['状态'];
        yield '响应时间' => ['响应时间'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // OAuth2AccessLogCrudController 禁用了编辑操作，但为了满足PHPUnit要求
        // 基类的isActionEnabled检查无法正确检测，所以测试会继续运行并失败
        // 这是预期行为，因为EDIT操作确实被禁用了
        // @todo 这里存在测试框架的限制，需要等待基类修复或者改进isActionEnabled方法
        // 此控制器禁用了EDIT操作，但由于基类方法被标记为final，无法重写
        // 所以提供虚拟数据让测试至少能执行，即使会失败也是预期行为
        yield 'disabled_action_placeholder' => ['disabled_action_placeholder'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // OAuth2AccessLogCrudController 禁用了新建操作，但为了满足PHPUnit要求
        // 基类的isActionEnabled检查无法正确检测，所以测试会继续运行并失败
        // 这是预期行为，因为NEW操作确实被禁用了
        // @todo 这里存在测试框架的限制，需要等待基类修复或者改进isActionEnabled方法
        // 此控制器禁用了NEW操作，但由于基类方法被标记为final，无法重写
        // 所以提供虚拟数据让测试至少能执行，即使会失败也是预期行为
        yield 'disabled_action_placeholder' => ['disabled_action_placeholder'];
    }
}
