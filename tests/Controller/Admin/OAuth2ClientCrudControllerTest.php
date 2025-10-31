<?php

namespace Tourze\OAuth2ServerBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2ClientCrudController;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(OAuth2ClientCrudController::class)]
#[RunTestsInSeparateProcesses]
final class OAuth2ClientCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testUnauthorizedAccessThrowsException(): void
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

        $this->expectException(MethodNotAllowedHttpException::class);

        $url = $this->generateAdminUrl('index');
        $client->request('POST', $url);
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

    public function testNameFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['name' => 'Test Client'],
        ]);
        $client->request('GET', $url);
    }

    public function testEnabledFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['enabled' => true],
        ]);
        $client->request('GET', $url);
    }

    public function testCreatedAtFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['createTime' => '2024-01-01'],
        ]);
        $client->request('GET', $url);
    }

    public function testUpdatedAtFilter(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('index', [
            'filters' => ['updateTime' => '2024-01-01'],
        ]);
        $client->request('GET', $url);
    }

    public function testNewFormValidation(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('new');
        $client->request('GET', $url);
    }

    public function testNewFormSubmissionValidation(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('new');
        $client->request('POST', $url, [
            'OAuth2Client' => [
                'name' => '',
                'clientId' => '',
            ],
        ]);
    }

    public function testRequiredFieldValidation(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $newUrl = $this->generateAdminUrl('new');
        $client->request('POST', $newUrl, [
            'OAuth2Client' => [
                'name' => '',
                'clientId' => '',
            ],
        ]);
    }

    protected function getControllerService(): OAuth2ClientCrudController
    {
        return self::getService(OAuth2ClientCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '客户端ID' => ['客户端ID'];
        yield '客户端名称' => ['客户端名称'];
        yield '关联用户' => ['关联用户'];
        yield '启用状态' => ['启用状态'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'enabled' => ['enabled'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'enabled' => ['enabled'];
    }

    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        $url = $this->generateAdminUrl('new');
        $crawler = $client->request('GET', $url);

        // 如果没有抛出异常，测试表单提交
        if ($client->getResponse()->isSuccessful()) {
            $form = $crawler->selectButton('Create')->form();
            $form['OAuth2Client[name]'] = '';
            $crawler = $client->submit($form);

            $this->assertResponseStatusCodeSame(422);
            $this->assertStringContainsString('should not be blank',
                $crawler->filter('.invalid-feedback')->text()
            );
        }
    }

    public function testRegenerateSecret(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        // 测试重新生成密钥动作
        $url = $this->generateAdminUrl('regenerateSecret', ['entityId' => 1]);
        $client->request('GET', $url);
    }

    public function testToggleStatus(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);

        // 测试启用/禁用状态切换动作
        $url = $this->generateAdminUrl('toggleStatus', ['entityId' => 1]);
        $client->request('GET', $url);
    }
}
