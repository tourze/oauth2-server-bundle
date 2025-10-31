# OAuth2 Server Bundle

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

A comprehensive OAuth2 server implementation for Symfony applications. This bundle provides complete OAuth2 
authorization server functionality including client management, authorization codes, access tokens, and 
administrative interfaces.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Available Controllers](#available-controllers)
- [Services](#services)
- [Advanced Usage](#advanced-usage)
- [Administrative Features](#administrative-features)
- [Security Considerations](#security-considerations)
- [Testing](#testing)
- [Contributing](#contributing)
- [Dependencies](#dependencies)
- [Support](#support)
- [License](#license)

## Features

- 🔐 **Complete OAuth2 Implementation**: Supports multiple grant types including client credentials and 
  authorization code flows
- 👥 **Client Management**: Full CRUD operations for OAuth2 clients with EasyAdmin integration
- 🔑 **Access Token Management**: Secure token generation and validation
- 📊 **Access Logging**: Track and monitor OAuth2 access patterns
- 🎯 **Scope Support**: Fine-grained permission control with scopes
- 🔄 **PKCE Support**: Proof Key for Code Exchange for enhanced security
- 🛡️ **Security Features**: Built-in protection against common OAuth2 vulnerabilities
- 🎛️ **Admin Interface**: Pre-built administration controllers for managing clients and logs

## Installation

### Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher
- EasyAdmin Bundle 4.x

### Install via Composer

Install the bundle via Composer:

```bash
composer require tourze/oauth2-server-bundle
```

## Configuration

### 1. Register the Bundle

Add the bundle to your `config/bundles.php`:

```php
return [
    // ... other bundles
    Tourze\OAuth2ServerBundle\OAuth2ServerBundle::class => ['all' => true],
];
```

### 2. Database Setup

Run database migrations to create required tables:

```bash
php bin/console doctrine:migrations:migrate
```

The bundle creates three main tables:
- `oauth2_client` - OAuth2 client configurations
- `authorization_code` - Temporary authorization codes
- `oauth2_access_log` - Access tracking logs

### 3. Routing Configuration

Add OAuth2 endpoints to your routing:

```yaml
# config/routes.yaml
oauth2_server:
    resource: '@OAuth2ServerBundle/Resources/config/routes.yaml'
    prefix: /oauth2
```

## Usage

### Creating OAuth2 Clients

Use the admin interface or create clients programmatically:

```php
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

$client = new OAuth2Client();
$client->setClientId('your-client-id');
$client->setClientSecret('your-client-secret');
$client->setName('My Application');
$client->setUser($user); // Associate with a user
$client->setGrantTypes(['client_credentials', 'authorization_code']);
$client->setRedirectUris(['https://yourapp.com/callback']);

$entityManager->persist($client);
$entityManager->flush();
```

### Client Credentials Grant

Request access tokens using client credentials:

```bash
curl -X POST http://yourapp.com/oauth2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id=your-client-id&client_secret=your-client-secret"
```

### Authorization Code Grant

1. Redirect users to the authorization endpoint:

```text
GET /oauth2/authorize?response_type=code&client_id=your-client-id&redirect_uri=https://yourapp.com/callback
```

2. Exchange authorization code for access token:

```bash
curl -X POST http://yourapp.com/oauth2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=authorization_code&code=auth-code&client_id=your-client-id&client_secret=your-client-secret&redirect_uri=https://yourapp.com/callback"
```

## Available Controllers

### Admin Controllers

- **OAuth2ClientCrudController**: Manage OAuth2 clients
- **AuthorizationCodeCrudController**: View authorization codes
- **OAuth2AccessLogCrudController**: Monitor access logs

### API Controllers

- **AuthorizeController**: Handle authorization requests
- **TokenController**: Issue and manage access tokens

## Services

### OAuth2ClientService

Manages OAuth2 client operations:

```php
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;

// Validate client credentials
$client = $clientService->validateClient($clientId, $clientSecret);

// Check grant type support
$supports = $clientService->supportsGrantType($client, 'client_credentials');
```

### AuthorizationService

Handles OAuth2 authorization flows:

```php
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

// Handle client credentials grant
$accessToken = $authorizationService->handleClientCredentialsGrant(
    $clientId,
    $clientSecret,
    $scopes
);
```

### AccessLogService

Tracks OAuth2 access patterns:

```php
use Tourze\OAuth2ServerBundle\Service\AccessLogService;

// Log access attempt
$logService->logAccess($client, $request, $response);
```

## Advanced Usage

### Custom Grant Types

Extend the bundle to support custom OAuth2 grant types:

```php
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

class CustomGrantHandler
{
    public function __construct(
        private AuthorizationService $authorizationService
    ) {}

    public function handleCustomGrant(array $parameters): array
    {
        // Implement custom grant logic
        // Validate parameters
        // Generate access token
        return $this->authorizationService->generateAccessToken($client, $scopes);
    }
}
```

### Token Customization

Customize access token generation and validation:

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

### Scope Validation

Implement advanced scope validation logic:

```php
class ScopeValidator
{
    public function validateScopes(OAuth2Client $client, array $requestedScopes): array
    {
        $allowedScopes = $client->getScopes();
        
        // Filter and validate requested scopes
        return array_intersect($requestedScopes, $allowedScopes);
    }
}
```

### Event Listeners

Listen to OAuth2 events for custom logic:

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
        // Handle token issuance
    }
}
```

## Administrative Features

The bundle includes EasyAdmin integration for:
- Client management with secret regeneration
- Access log monitoring
- Authorization code tracking
- Client status toggling

## Security Considerations

- **Client Secret Storage**: Client secrets are stored securely (consider using encryption)
- **Token Validation**: Built-in token validation and expiration handling
- **Scope Enforcement**: Implement scope-based access control in your application
- **HTTPS Required**: Always use HTTPS in production for OAuth2 flows
- **PKCE Support**: Recommended for public clients and mobile applications

## Testing

Run the test suite:

```bash
./vendor/bin/phpunit packages/oauth2-server-bundle/tests
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## Dependencies

This bundle integrates with several other Tourze packages:
- `tourze/access-token-bundle` - Access token management
- `tourze/doctrine-*-bundle` - Doctrine extensions
- `tourze/easy-admin-menu-bundle` - Admin menu integration

## Support

For support and questions, please check the documentation or create an issue in the repository.

## License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.