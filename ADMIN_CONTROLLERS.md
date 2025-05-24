# OAuth2 Server Bundle 后台管理控制器

本包提供了完整的 EasyAdmin 后台管理界面，用于管理 OAuth2 服务端的各种资源。

## 控制器列表

### 1. OAuth2ClientCrudController
- **功能**: 管理 OAuth2 客户端应用
- **路径**: `Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2ClientCrudController`
- **支持操作**: 查看、创建、编辑、删除、详情
- **主要功能**:
  - 客户端应用注册和管理
  - 客户端密钥和配置管理
  - 重定向URI配置
  - 授权类型和权限范围设置
  - 客户端启用/禁用状态管理

### 2. AuthorizationCodeCrudController  
- **功能**: 查看和管理 OAuth2 授权码
- **路径**: `Tourze\OAuth2ServerBundle\Controller\Admin\AuthorizationCodeCrudController`
- **支持操作**: 查看、详情、删除（只读模式）
- **主要功能**:
  - 授权码生成记录查看
  - 授权码有效性状态监控
  - PKCE 支持信息查看
  - 授权用户和客户端关联信息

### 3. OAuth2AccessLogCrudController
- **功能**: 查看 OAuth2 端点访问日志
- **路径**: `Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2AccessLogCrudController`  
- **支持操作**: 查看、详情、删除（只读模式）
- **主要功能**:
  - 端点访问记录查看
  - 访问统计和性能监控
  - 错误日志和异常检测
  - 安全审计和IP监控

## 菜单服务

### AdminMenu 服务
- **功能**: 自动在后台管理界面创建 OAuth2 管理菜单
- **路径**: `Tourze\OAuth2ServerBundle\Service\AdminMenu`
- **菜单结构**:
  - OAuth2管理 (fas fa-shield-alt)
    - 客户端管理 (fas fa-users)
    - 授权码记录 (fas fa-key)
    - 访问日志 (fas fa-list)

该服务实现了 `MenuProviderInterface` 接口，会自动注册并在后台管理界面显示菜单。

## 使用方法

### 自动菜单（推荐）

当你安装了 `easy-admin-menu-bundle` 时，OAuth2 菜单会自动出现在后台管理界面中，无需手动配置。

### 手动配置菜单（可选）

如果你不使用 `easy-admin-menu-bundle`，可以在 Dashboard 控制器中手动注册菜单项：

```php
use Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2ClientCrudController;
use Tourze\OAuth2ServerBundle\Controller\Admin\AuthorizationCodeCrudController;
use Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2AccessLogCrudController;

public function configureMenuItems(): iterable
{
    yield MenuItem::section('OAuth2 管理');
    
    yield MenuItem::linkToCrud('客户端管理', 'fas fa-users', OAuth2Client::class)
        ->setController(OAuth2ClientCrudController::class);
        
    yield MenuItem::linkToCrud('授权码记录', 'fas fa-key', AuthorizationCode::class)
        ->setController(AuthorizationCodeCrudController::class);
        
    yield MenuItem::linkToCrud('访问日志', 'fas fa-list', OAuth2AccessLog::class)
        ->setController(OAuth2AccessLogCrudController::class);
}
```

### 权限控制

可以通过 Symfony 的安全配置来控制访问权限：

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/admin/oauth2, roles: ROLE_OAUTH2_ADMIN }
```

### 自定义配置

每个控制器都支持进一步的自定义：

```php
// 继承并自定义控制器
class CustomOAuth2ClientCrudController extends OAuth2ClientCrudController
{
    public function configureFields(string $pageName): iterable
    {
        // 自定义字段配置
        yield from parent::configureFields($pageName);
        
        // 添加自定义字段
        if ($pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('customField', '自定义字段');
        }
    }
}
```

## 特性说明

### 数据安全
- 客户端密钥在列表中不显示，仅在创建时返回明文
- 授权码值在列表中只显示前16位
- 访问日志中的敏感参数已自动过滤
- 支持只读模式，防止误操作

### 性能优化
- 使用关联查询减少数据库查询次数
- 分页显示，避免大数据量加载问题
- 索引优化的搜索和过滤功能

### 用户体验
- 中文界面，友好的字段标签和帮助信息
- 丰富的过滤器和搜索功能
- 状态颜色标识，快速识别数据状态
- 响应式设计，支持移动端访问

## 注意事项

1. **安全性**: 确保后台管理界面有适当的访问控制
2. **性能**: 访问日志表可能数据量很大，建议定期清理
3. **备份**: 在删除重要数据前请确保有备份
4. **权限**: 建议只给必要的管理员分配 OAuth2 管理权限 