# OAuth2 服务端包

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/oauth2-server-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/oauth2-server-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/oauth2-server-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/oauth2-server-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)]
(https://github.com/tourze/php-monorepo/actions)
[![Coverage Status](https://img.shields.io/codecov/c/github/tourze/oauth2-server-bundle.svg?style=flat-square)]
(https://codecov.io/gh/tourze/oauth2-server-bundle)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/oauth2-server-bundle.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/tourze/oauth2-server-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/oauth2-server-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/oauth2-server-bundle)

为 Symfony 应用程序提供的完整 OAuth2 服务端实现。本包提供完整的 OAuth2 授权服务器功能，包括客户端管理、
授权码、访问令牌和管理界面。

## 目录

- [特性](#特性)
- [安装](#安装)
- [配置](#配置)
- [使用](#使用)
- [可用控制器](#可用控制器)
- [服务](#服务)
- [高级用法](#高级用法)
- [管理功能](#管理功能)
- [安全考虑](#安全考虑)
- [测试](#测试)
- [贡献](#贡献)
- [依赖](#依赖)
- [支持](#支持)
- [许可证](#许可证)

## 特性

- 🔐 **完整的 OAuth2 实现**: 支持多种授权类型，包括客户端凭证和授权码模式
- 👥 **客户端管理**: 完整的 OAuth2 客户端 CRUD 操作，集成 EasyAdmin
- 🔑 **访问令牌管理**: 安全的令牌生成和验证
- 📊 **访问日志**: 跟踪和监控 OAuth2 访问模式
- 🎯 **作用域支持**: 使用作用域进行细粒度权限控制
- 🔄 **PKCE 支持**: 代码交换证明密钥，增强安全性
- 🛡️ **安全特性**: 内置防护常见的 OAuth2 漏洞
- 🎛️ **管理界面**: 预构建的管理控制器用于管理客户端和日志

## 安装

### 系统要求

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本  
- Doctrine ORM 3.0 或更高版本
- EasyAdmin Bundle 4.x

### 通过 Composer 安装

通过 Composer 安装包：

```bash
composer require tourze/oauth2-server-bundle
```

## 配置

### 1. 注册包

将包添加到 `config/bundles.php`：

```php
return [
    // ... 其他包
    Tourze\OAuth2ServerBundle\OAuth2ServerBundle::class => ['all' => true],
];
```

### 2. 数据库设置

运行数据库迁移以创建所需的表：

```bash
php bin/console doctrine:migrations:migrate
```

该包创建三个主要表：
- `oauth2_client` - OAuth2 客户端配置
- `authorization_code` - 临时授权码
- `oauth2_access_log` - 访问跟踪日志

### 3. 路由配置

将 OAuth2 端点添加到路由中：

```yaml
# config/routes.yaml
oauth2_server:
    resource: '@OAuth2ServerBundle/Resources/config/routes.yaml'
    prefix: /oauth2
```

## 使用

### 创建 OAuth2 客户端

使用管理界面或编程方式创建客户端：

```php
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

$client = new OAuth2Client();
$client->setClientId('your-client-id');
$client->setClientSecret('your-client-secret');
$client->setName('My Application');
$client->setUser($user); // 关联用户
$client->setGrantTypes(['client_credentials', 'authorization_code']);
$client->setRedirectUris(['https://yourapp.com/callback']);

$entityManager->persist($client);
$entityManager->flush();
```

### 客户端凭证授权

使用客户端凭证请求访问令牌：

```bash
curl -X POST http://yourapp.com/oauth2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id=your-client-id&client_secret=your-client-secret"
```

### 授权码授权

1. 将用户重定向到授权端点：

```text
GET /oauth2/authorize?response_type=code&client_id=your-client-id&redirect_uri=https://yourapp.com/callback
```

2. 交换授权码获取访问令牌：

```bash
curl -X POST http://yourapp.com/oauth2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=authorization_code&code=auth-code&client_id=your-client-id&client_secret=your-client-secret&redirect_uri=https://yourapp.com/callback"
```

## 可用控制器

### 管理控制器

- **OAuth2ClientCrudController**: 管理 OAuth2 客户端
- **AuthorizationCodeCrudController**: 查看授权码
- **OAuth2AccessLogCrudController**: 监控访问日志

### API 控制器

- **AuthorizeController**: 处理授权请求
- **TokenController**: 签发和管理访问令牌

## 服务

### OAuth2ClientService

管理 OAuth2 客户端操作：

```php
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;

// 验证客户端凭证
$client = $clientService->validateClient($clientId, $clientSecret);

// 检查授权类型支持
$supports = $clientService->supportsGrantType($client, 'client_credentials');
```

### AuthorizationService

处理 OAuth2 授权流程：

```php
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

// 处理客户端凭证授权
$accessToken = $authorizationService->handleClientCredentialsGrant(
    $clientId,
    $clientSecret,
    $scopes
);
```

### AccessLogService

跟踪 OAuth2 访问模式：

```php
use Tourze\OAuth2ServerBundle\Service\AccessLogService;

// 记录访问尝试
$logService->logAccess($client, $request, $response);
```

## 高级用法

### 自定义授权类型

扩展包以支持自定义 OAuth2 授权类型：

```php
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

class CustomGrantHandler
{
    public function __construct(
        private AuthorizationService $authorizationService
    ) {}

    public function handleCustomGrant(array $parameters): array
    {
        // 实现自定义授权逻辑
        // 验证参数
        // 生成访问令牌
        return $this->authorizationService->generateAccessToken($client, $scopes);
    }
}
```

### 令牌自定义

自定义访问令牌生成和验证：

```php
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

class TokenCustomizer
{
    public function customizeTokenPayload(OAuth2Client $client, array $scopes): array
    {
        return [
            'client_id' => $client->getClientId(),
            'scopes' => $scopes,
            'custom_claim' => 'custom_value',
            'issued_at' => time(),
        ];
    }
}
```

### 作用域验证

实现高级作用域验证逻辑：

```php
class ScopeValidator
{
    public function validateScopes(OAuth2Client $client, array $requestedScopes): array
    {
        $allowedScopes = $client->getScopes();
        
        // 过滤和验证请求的作用域
        return array_intersect($requestedScopes, $allowedScopes);
    }
}
```

### 事件监听器

监听 OAuth2 事件以实现自定义逻辑：

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OAuth2EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'oauth2.token_issued' => 'onTokenIssued',
            'oauth2.client_authorized' => 'onClientAuthorized',
        ];
    }

    public function onTokenIssued(TokenIssuedEvent $event): void
    {
        // 处理令牌签发
    }
}
```

## 管理功能

该包包含 EasyAdmin 集成，用于：
- 客户端管理和密钥重新生成
- 访问日志监控
- 授权码跟踪
- 客户端状态切换

## 安全考虑

- **客户端密钥存储**: 客户端密钥安全存储（考虑使用加密）
- **令牌验证**: 内置令牌验证和过期处理
- **作用域强制**: 在应用程序中实现基于作用域的访问控制
- **要求 HTTPS**: 生产环境中 OAuth2 流程始终使用 HTTPS
- **PKCE 支持**: 推荐用于公共客户端和移动应用程序

## 测试

运行测试套件：

```bash
./vendor/bin/phpunit packages/oauth2-server-bundle/tests
```

## 贡献

1. Fork 仓库
2. 创建功能分支
3. 为新功能编写测试
4. 确保所有测试通过
5. 提交拉取请求

## 依赖

该包与几个其他 Tourze 包集成：
- `tourze/access-token-bundle` - 访问令牌管理
- `tourze/doctrine-*-bundle` - Doctrine 扩展
- `tourze/easy-admin-menu-bundle` - 管理菜单集成

## 支持

如需支持和问题，请查看文档或在仓库中创建 issue。

## 许可证

该包采用 MIT 许可证。详情请参阅 [LICENSE](LICENSE) 文件。