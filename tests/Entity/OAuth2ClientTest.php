<?php

declare(strict_types=1);

namespace Tourze\OAuth2ServerBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * OAuth2Client实体测试类
 *
 * @internal
 */
#[CoversClass(OAuth2Client::class)]
final class OAuth2ClientTest extends AbstractEntityTestCase
{
    // Getter/setter 测试由 AbstractEntityTest 自动提供

    protected function setUpContainer(): void
    {
        // 这个测试不需要额外的设置
    }

    /**
     * 创建被测实体实例
     */
    protected function createEntity(): OAuth2Client
    {
        return new OAuth2Client();
    }

    /**
     * 提供要测试的属性及其示例值
     */
    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'name' => ['name', 'Test Client'],
            'clientId' => ['clientId', 'test_client_id'],
            'clientSecret' => ['clientSecret', 'test_secret'],
            'redirectUris' => ['redirectUris', ['https://example.com/callback']],
            'grantTypes' => ['grantTypes', ['authorization_code', 'client_credentials']],
            'scopes' => ['scopes', ['read', 'write']],
            'confidential' => ['confidential', true],
            'enabled' => ['enabled', true],
            'accessTokenLifetime' => ['accessTokenLifetime', 3600],
            'refreshTokenLifetime' => ['refreshTokenLifetime', 1209600],
            'user' => ['user', null],
        ];
    }

    public function testConstructorCreatesEmptyCollections(): void
    {
        $client = new OAuth2Client();

        $this->assertCount(0, $client->getAuthorizationCodes());
        $this->assertSame([], $client->getRedirectUris());
        $this->assertSame(['client_credentials'], $client->getGrantTypes());
        $this->assertTrue($client->isConfidential());
        $this->assertTrue($client->isEnabled());
        $this->assertSame(3600, $client->getAccessTokenLifetime());
        $this->assertSame(1209600, $client->getRefreshTokenLifetime());
    }

    public function testAddRedirectUriAddsNewUri(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';

        $client->addRedirectUri($uri);

        $this->assertSame([$uri], $client->getRedirectUris());
    }

    public function testAddRedirectUriDoesNotAddDuplicate(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';

        $client->addRedirectUri($uri);
        $client->addRedirectUri($uri);

        $this->assertSame([$uri], $client->getRedirectUris());
    }

    public function testRemoveRedirectUriRemovesExistingUri(): void
    {
        $client = new OAuth2Client();
        $uri1 = 'https://example.com/callback';
        $uri2 = 'https://app.example.com/oauth';

        $client->setRedirectUris([$uri1, $uri2]);
        $client->removeRedirectUri($uri1);

        $this->assertSame([$uri2], array_values($client->getRedirectUris()));
    }

    public function testRemoveRedirectUriDoesNothingForNonExistentUri(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';

        $client->setRedirectUris([$uri]);
        $client->removeRedirectUri('https://other.com/callback');

        $this->assertSame([$uri], $client->getRedirectUris());
    }

    public function testHasRedirectUriReturnsTrueForExistingUri(): void
    {
        $client = new OAuth2Client();
        $uri = 'https://example.com/callback';

        $client->addRedirectUri($uri);

        $this->assertTrue($client->hasRedirectUri($uri));
    }

    public function testHasRedirectUriReturnsFalseForNonExistentUri(): void
    {
        $client = new OAuth2Client();

        $this->assertFalse($client->hasRedirectUri('https://example.com/callback'));
    }

    public function testSupportsGrantTypeReturnsTrueForSupportedType(): void
    {
        $client = new OAuth2Client();
        $client->setGrantTypes(['authorization_code', 'refresh_token']);

        $this->assertTrue($client->supportsGrantType('authorization_code'));
        $this->assertTrue($client->supportsGrantType('refresh_token'));
    }

    public function testSupportsGrantTypeReturnsFalseForUnsupportedType(): void
    {
        $client = new OAuth2Client();
        $client->setGrantTypes(['authorization_code']);

        $this->assertFalse($client->supportsGrantType('client_credentials'));
    }

    public function testSupportsCodeChallengeMethodReturnsTrueForSupportedMethod(): void
    {
        $client = new OAuth2Client();
        $client->setCodeChallengeMethod(['plain', 'S256']);

        $this->assertTrue($client->supportsCodeChallengeMethod('plain'));
        $this->assertTrue($client->supportsCodeChallengeMethod('S256'));
    }

    public function testSupportsCodeChallengeMethodReturnsFalseForUnsupportedMethod(): void
    {
        $client = new OAuth2Client();
        $client->setCodeChallengeMethod(['S256']);

        $this->assertFalse($client->supportsCodeChallengeMethod('plain'));
    }

    public function testSupportsCodeChallengeMethodReturnsFalseWhenMethodsNull(): void
    {
        $client = new OAuth2Client();
        $client->setCodeChallengeMethod(null);

        $this->assertFalse($client->supportsCodeChallengeMethod('S256'));
    }

    public function testAddAuthorizationCodeAddsCodeToCollection(): void
    {
        $client = new OAuth2Client();
        // Mock具体类是必要的，因为：
        // 1) AuthorizationCode实体类包含业务逻辑方法，需要验证特定的行为
        // 2) 没有合适的接口可以替代
        // 3) 单元测试需要控制实体的状态和行为
        $authCode = $this->createMock(AuthorizationCode::class);
        $authCode->expects($this->once())->method('setClient')->with($client);

        $client->addAuthorizationCode($authCode);

        $this->assertCount(1, $client->getAuthorizationCodes());
        $this->assertTrue($client->getAuthorizationCodes()->contains($authCode));
    }

    public function testAddAuthorizationCodeDoesNotAddDuplicate(): void
    {
        $client = new OAuth2Client();
        // Mock具体类是必要的，因为：
        // 1) AuthorizationCode实体类包含业务逻辑方法，需要验证特定的行为
        // 2) 没有合适的接口可以替代
        // 3) 单元测试需要控制实体的状态和行为
        $authCode = $this->createMock(AuthorizationCode::class);
        $authCode->expects($this->once())->method('setClient')->with($client);

        $client->addAuthorizationCode($authCode);
        $client->addAuthorizationCode($authCode);

        $this->assertCount(1, $client->getAuthorizationCodes());
    }

    public function testRemoveAuthorizationCodeRemovesCodeFromCollection(): void
    {
        $client = new OAuth2Client();
        // Mock具体类是必要的，因为：
        // 1) AuthorizationCode实体类包含业务逻辑方法，需要验证特定的行为
        // 2) 没有合适的接口可以替代
        // 3) 单元测试需要控制实体的状态和行为
        $authCode = $this->createMock(AuthorizationCode::class);

        // 先添加到集合中
        $client->addAuthorizationCode($authCode);

        // 模拟getClient返回当前客户端，然后期望setClient被调用并传入null
        $authCode->method('getClient')->willReturn($client);
        $authCode->expects($this->once())->method('setClient')->with(self::isNull());

        $client->removeAuthorizationCode($authCode);

        $this->assertCount(0, $client->getAuthorizationCodes());
    }

    public function testRemoveAuthorizationCodeDoesNothingForNonExistentCode(): void
    {
        $client = new OAuth2Client();
        // Mock具体类是必要的，因为：
        // 1) AuthorizationCode实体类包含业务逻辑方法，需要验证特定的行为
        // 2) 没有合适的接口可以替代
        // 3) 单元测试需要控制实体的状态和行为
        $authCode = $this->createMock(AuthorizationCode::class);
        $authCode->expects($this->never())->method('setClient');

        $client->removeAuthorizationCode($authCode);
    }

    public function testToStringReturnsName(): void
    {
        $client = new OAuth2Client();
        $client->setName('Test App');

        $this->assertSame('Test App', (string) $client);
    }

    public function testToStringReturnsClientIdWhenNameEmpty(): void
    {
        $client = new OAuth2Client();
        $client->setClientId('test_client');

        $this->assertSame('test_client', (string) $client);
    }

    public function testToStringReturnsEmptyStringWhenBothEmpty(): void
    {
        $client = new OAuth2Client();

        $this->assertSame('', (string) $client);
    }
}
