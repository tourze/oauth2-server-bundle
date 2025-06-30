<?php

namespace Tourze\OAuth2ServerBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;

/**
 * OAuth2访问日志管理控制器
 */
class OAuth2AccessLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OAuth2AccessLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('访问日志')
            ->setEntityLabelInPlural('访问日志管理')
            ->setPageTitle('index', 'OAuth2访问日志')
            ->setPageTitle('detail', '访问日志详情')
            ->setHelp('index', '查看OAuth2端点的访问记录，用于安全审计和异常检测')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['endpoint', 'clientId', 'ipAddress', 'userId'])
            ->setPaginatorPageSize(50);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('endpoint', '端点')
            ->setMaxLength(50)
            ->setHelp('访问的OAuth2端点名称');

        yield TextField::new('clientId', '客户端ID')
            ->setMaxLength(255)
            ->setHelp('请求的客户端标识符')
            ->formatValue(function ($value) {
                return $value !== '' ? $value : '-';
            });

        yield AssociationField::new('client', '客户端')
            ->autocomplete()
            ->setHelp('关联的OAuth2客户端对象')
            ->hideOnIndex();

        yield TextField::new('userId', '用户ID')
            ->setMaxLength(255)
            ->setHelp('执行操作的用户标识符')
            ->formatValue(function ($value) {
                return $value !== '' ? $value : '-';
            });

        yield TextField::new('ipAddress', 'IP地址')
            ->setMaxLength(45)
            ->setHelp('请求来源IP地址');

        yield TextField::new('userAgent', 'User Agent')
            ->setMaxLength(100)
            ->hideOnIndex()
            ->setHelp('客户端浏览器标识信息')
            ->formatValue(function ($value) {
                return $value ? (strlen($value) > 80 ? substr($value, 0, 80) . '...' : $value) : '-';
            });

        yield TextField::new('method', '请求方法')
            ->setMaxLength(10)
            ->setHelp('HTTP请求方法');

        yield ArrayField::new('requestParams', '请求参数')
            ->hideOnIndex()
            ->setHelp('请求携带的参数（敏感信息已过滤）');

        yield TextField::new('status', '状态')
            ->setMaxLength(20)
            ->setHelp('请求处理状态')
            ->formatValue(function ($value) {
                $statusColors = [
                    'success' => '✅ 成功',
                    'error' => '❌ 错误',
                ];
                return $statusColors[$value] ?? $value;
            });

        yield TextField::new('errorCode', '错误代码')
            ->setMaxLength(100)
            ->hideOnIndex()
            ->setHelp('错误类型代码')
            ->formatValue(function ($value) {
                return $value !== '' ? $value : '-';
            });

        yield TextField::new('errorMessage', '错误信息')
            ->hideOnIndex()
            ->setHelp('详细错误描述信息')
            ->formatValue(function ($value) {
                return $value ? (strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value) : '-';
            });

        yield IntegerField::new('responseTime', '响应时间')
            ->setHelp('请求处理耗时（毫秒）')
            ->formatValue(function ($value) {
                if ($value === null) return '-';
                if ($value < 100) return "{$value}ms";
                if ($value < 1000) return "<span style='color: orange'>{$value}ms</span>";
                return "<span style='color: red'>{$value}ms</span>";
            });

        yield DateTimeField::new('createdAt', '创建时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '';
            });

        // 添加格式化的响应时间字段（虚拟字段）
        if ($pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('formattedResponseTime', '格式化响应时间')
                ->hideOnForm()
                ->formatValue(function ($value, $entity) {
                    return $entity instanceof OAuth2AccessLog ? $entity->getFormattedResponseTime() : '-';
                })
                ->setHelp('友好格式的响应时间显示');
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::DELETE]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 构建状态选项
        $statusChoices = [
            '成功' => 'success',
            '错误' => 'error',
        ];

        // 构建端点选项
        $endpointChoices = [
            '令牌端点' => 'token',
            '授权端点' => 'authorize',
        ];

        return $filters
            ->add(ChoiceFilter::new('endpoint', '端点')->setChoices($endpointChoices))
            ->add(TextFilter::new('clientId', '客户端ID'))
            ->add(EntityFilter::new('client', '客户端'))
            ->add(TextFilter::new('userId', '用户ID'))
            ->add(TextFilter::new('ipAddress', 'IP地址'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
            ->add(TextFilter::new('errorCode', '错误代码'))
            ->add(DateTimeFilter::new('createdAt', '创建时间'));
    }

    /**
     * 自定义查询构建器以优化性能
     */
    public function createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters): \Doctrine\ORM\QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->leftJoin('entity.client', 'client')
            ->addSelect('client')
            ->orderBy('entity.id', 'DESC');
    }
}
