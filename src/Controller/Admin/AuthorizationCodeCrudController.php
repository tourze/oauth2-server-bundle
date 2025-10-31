<?php

namespace Tourze\OAuth2ServerBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;

/**
 * OAuth2授权码管理控制器
 * @extends AbstractCrudController<AuthorizationCode>
 */
#[AdminCrud(
    routePath: '/oauth2-server/authorization-code',
    routeName: 'oauth2_server_authorization_code'
)]
final class AuthorizationCodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuthorizationCode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('授权码')
            ->setEntityLabelInPlural('授权码管理')
            ->setPageTitle('index', '授权码列表')
            ->setPageTitle('detail', '授权码详情')
            ->setHelp('index', '查看OAuth2授权码的生成和使用情况，授权码具有短暂有效期且只能使用一次')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['code', 'redirectUri'])
            ->setPaginatorPageSize(30)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable
    {
        yield from $this->getBasicFields();
        yield from $this->getAssociationFields();
        yield from $this->getRedirectAndTimeFields();
        yield from $this->getPKCEFields();
        yield from $this->getStatusFields();

        if (Crud::PAGE_INDEX === $pageName || Crud::PAGE_DETAIL === $pageName) {
            yield from $this->getValidityFields();
        }
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getBasicFields(): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('code', '授权码')
            ->setMaxLength(128)
            ->setHelp('生成的授权码值，用于换取访问令牌')
            ->formatValue(function ($value) {
                return is_string($value) && '' !== $value ? substr($value, 0, 16) . '...' : '';
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getAssociationFields(): iterable
    {
        yield AssociationField::new('client', '客户端')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('关联的OAuth2客户端')
        ;

        yield AssociationField::new('user', '授权用户')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('进行授权的用户')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getRedirectAndTimeFields(): iterable
    {
        yield TextField::new('redirectUri', '重定向URI')
            ->setMaxLength(255)
            ->setHelp('授权完成后的回调地址')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('expireTime', '过期时间')
            ->setRequired(true)
            ->setHelp('授权码的过期时间')
            ->formatValue(function ($value) {
                if (!$value instanceof \DateTimeInterface) {
                    return '';
                }
                $now = new \DateTime();
                $status = $value > $now ? ' (有效)' : ' (已过期)';

                return $value->format('Y-m-d H:i:s') . $status;
            })
        ;

        yield ArrayField::new('scopes', '权限范围')
            ->hideOnIndex()
            ->setHelp('授权的权限作用域')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getPKCEFields(): iterable
    {
        yield TextField::new('codeChallenge', 'PKCE挑战')
            ->setMaxLength(50)
            ->hideOnIndex()
            ->setHelp('PKCE代码挑战值')
            ->formatValue(function ($value) {
                return is_string($value) && '' !== $value ? substr($value, 0, 20) . '...' : '';
            })
        ;

        yield TextField::new('codeChallengeMethod', 'PKCE方法')
            ->setMaxLength(10)
            ->hideOnIndex()
            ->setHelp('PKCE代码挑战方法')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getStatusFields(): iterable
    {
        yield BooleanField::new('used', '已使用')
            ->setHelp('授权码是否已被使用')
        ;

        yield TextField::new('state', '状态参数')
            ->hideOnIndex()
            ->setHelp('用于防CSRF攻击的状态参数')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '';
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getValidityFields(): iterable
    {
        yield BooleanField::new('isValid', '有效状态')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                return $entity instanceof AuthorizationCode ? $entity->isValid() : false;
            })
            ->setHelp('授权码是否仍然有效（未过期且未使用）')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('code', '授权码'))
            ->add(EntityFilter::new('client', '客户端'))
            ->add(EntityFilter::new('user', '授权用户'))
            ->add(BooleanFilter::new('used', '已使用'))
            ->add(DateTimeFilter::new('expireTime', '过期时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
