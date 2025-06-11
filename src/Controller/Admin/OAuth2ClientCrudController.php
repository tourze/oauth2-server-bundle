<?php

namespace Tourze\OAuth2ServerBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;

/**
 * OAuth2客户端管理控制器
 */
class OAuth2ClientCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly OAuth2ClientService $clientService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return OAuth2Client::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('OAuth2客户端')
            ->setEntityLabelInPlural('OAuth2客户端管理')
            ->setPageTitle('index', 'OAuth2客户端列表')
            ->setPageTitle('new', '创建OAuth2客户端')
            ->setPageTitle('edit', '编辑OAuth2客户端')
            ->setPageTitle('detail', 'OAuth2客户端详情')
            ->setHelp('index', '管理系统中的OAuth2客户端应用，包括第三方应用和内部服务')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['clientId', 'name', 'description'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('clientId', '客户端ID')
            ->setMaxLength(80)
            ->hideOnForm()
            ->setHelp('系统自动生成的唯一标识符');

        yield TextField::new('name', '客户端名称')
            ->setMaxLength(255)
            ->setRequired(true)
            ->setHelp('用于标识客户端应用的友好名称');

        yield TextareaField::new('description', '描述')
            ->hideOnIndex()
            ->setHelp('客户端应用的详细描述信息');

        yield AssociationField::new('user', '关联用户')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('该客户端归属的系统用户');

        yield ArrayField::new('redirectUris', '重定向URI')
            ->hideOnIndex()
            ->setHelp('授权完成后允许的回调地址列表');

        yield ArrayField::new('grantTypes', '授权类型')
            ->hideOnIndex()
            ->setHelp('支持的OAuth2授权流程类型');

        yield ArrayField::new('scopes', '权限范围')
            ->hideOnIndex()
            ->setHelp('客户端可请求的权限作用域');

        yield BooleanField::new('confidential', '机密客户端')
            ->setHelp('是否需要客户端密钥验证')
            ->hideOnIndex();

        yield BooleanField::new('enabled', '启用状态')
            ->setHelp('是否启用该客户端');

        yield IntegerField::new('accessTokenLifetime', '访问令牌有效期')
            ->setHelp('访问令牌的有效期（秒）')
            ->hideOnIndex();

        yield IntegerField::new('refreshTokenLifetime', '刷新令牌有效期')
            ->setHelp('刷新令牌的有效期（秒）')
            ->hideOnIndex();

        yield ArrayField::new('codeChallengeMethod', 'PKCE方法')
            ->hideOnIndex()
            ->setHelp('支持的PKCE代码挑战方法');

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '';
            });

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '';
            });
    }

    public function configureActions(Actions $actions): Actions
    {
        // 创建重新生成密钥操作
        $regenerateSecret = Action::new('regenerateSecret', '重新生成密钥')
            ->linkToCrudAction('regenerateSecret')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-refresh')
            ->displayIf(function (OAuth2Client $client) {
                return $client->isConfidential();
            });

        // 创建启用/禁用操作
        $toggleStatus = Action::new('toggleStatus')
            ->linkToCrudAction('toggleStatus')
            ->setCssClass('btn btn-secondary')
            ->setIcon('fa fa-power-off');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $toggleStatus)
            ->add(Crud::PAGE_DETAIL, $regenerateSecret)
            ->add(Crud::PAGE_DETAIL, $toggleStatus)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, $toggleStatus, Action::DELETE]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('clientId', '客户端ID'))
            ->add(TextFilter::new('name', '客户端名称'))
            ->add(BooleanFilter::new('enabled', '启用状态'))
            ->add(BooleanFilter::new('confidential', '机密客户端'))
            ->add(EntityFilter::new('user', '关联用户'));
    }

    /**
     * 重新生成客户端密钥
     */
    #[Route('/admin/oauth2-client/{entityId}/regenerate-secret', name: 'admin_oauth2_client_regenerate_secret', methods: ['POST'])]
    public function regenerateSecret(AdminContext $context, Request $request): RedirectResponse
    {
        /** @var OAuth2Client $client */
        $client = $context->getEntity()->getInstance();
        
        if (!$client->isConfidential()) {
            $this->addFlash('danger', '公开客户端不需要密钥');
            return $this->redirectToDetail($client);
        }

        try {
            $newSecret = $this->clientService->regenerateClientSecret($client);
            
            $this->addFlash('success', 
                "客户端密钥已重新生成。新密钥: <strong>{$newSecret}</strong><br>" .
                "<small class='text-warning'>请立即保存此密钥，系统不会再次显示明文密钥</small>"
            );
        } catch  (\Throwable $e) {
            $this->addFlash('danger', '密钥重新生成失败: ' . $e->getMessage());
        }

        return $this->redirectToDetail($client);
    }

    /**
     * 切换客户端状态
     */
    #[Route('/admin/oauth2-client/{entityId}/toggle-status', name: 'admin_oauth2_client_toggle_status', methods: ['POST'])]
    public function toggleStatus(AdminContext $context, Request $request): RedirectResponse
    {
        /** @var OAuth2Client $client */
        $client = $context->getEntity()->getInstance();
        
        try {
            if ($client->isEnabled()) {
                $this->clientService->disableClient($client);
                $this->addFlash('success', '客户端已禁用');
            } else {
                $this->clientService->enableClient($client);
                $this->addFlash('success', '客户端已启用');
            }
        } catch  (\Throwable $e) {
            $this->addFlash('danger', '状态切换失败: ' . $e->getMessage());
        }

        return $this->redirectToIndex();
    }

    /**
     * 重定向到详情页
     */
    private function redirectToDetail(OAuth2Client $client): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($client->getId())
            ->generateUrl();

        return new RedirectResponse($url);
    }

    /**
     * 重定向到列表页
     */
    private function redirectToIndex(): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return new RedirectResponse($url);
    }
}
