<?php

declare(strict_types=1);

namespace Tourze\OAuth2ServerBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * AuthorizationCode实体测试类
 *
 * @internal
 */
#[CoversClass(AuthorizationCode::class)]
final class AuthorizationCodeTest extends AbstractEntityTestCase
{
    private OAuth2Client&MockObject $mockClient;

    private UserInterface&MockObject $mockUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock具体类是必要的，因为：
        // 1) OAuth2Client实体类包含业务逻辑方法，需要验证特定的行为
        // 2) 没有合适的接口可以替代
        // 3) 单元测试需要控制实体的状态和行为
        $this->mockClient = $this->createMock(OAuth2Client::class);
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->method('getUserIdentifier')->willReturn('test@example.com');
    }

    /**
     * 创建被测实体实例
     */
    protected function createEntity(): AuthorizationCode
    {
        return new AuthorizationCode();
    }

    /**
     * 提供要测试的属性及其示例值
     */
    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'code' => ['code', 'test_authorization_code'],
            'client' => ['client', null], // 关联实体，设为null
            'user' => ['user', null], // 关联实体，设为null
            'redirectUri' => ['redirectUri', 'https://example.com/callback'],
            'expireTime' => ['expireTime', new \DateTimeImmutable('+10 minutes')],
            'scopes' => ['scopes', ['read', 'write']],
            'codeChallenge' => ['codeChallenge', 'test_challenge'],
            'codeChallengeMethod' => ['codeChallengeMethod', 'S256'],
            'used' => ['used', true],
            'state' => ['state', 'random_state_value'],
            'createTime' => ['createTime', new \DateTimeImmutable()],
        ];
    }

    // Getter/setter 测试由 AbstractEntityTest 自动提供

    public function testIsExpiredReturnsFalseForFutureDate(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpireTime(new \DateTimeImmutable('+10 minutes'));

        $this->assertFalse($authCode->isExpired());
    }

    public function testIsExpiredReturnsTrueForPastDate(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpireTime(new \DateTimeImmutable('-1 minute'));

        $this->assertTrue($authCode->isExpired());
    }

    public function testIsValidReturnsTrueWhenNotExpiredAndNotUsed(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpireTime(new \DateTimeImmutable('+10 minutes'));
        $authCode->setUsed(false);

        $this->assertTrue($authCode->isValid());
    }

    public function testIsValidReturnsFalseWhenExpired(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpireTime(new \DateTimeImmutable('-1 minute'));
        $authCode->setUsed(false);

        $this->assertFalse($authCode->isValid());
    }

    public function testIsValidReturnsFalseWhenUsed(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpireTime(new \DateTimeImmutable('+10 minutes'));
        $authCode->setUsed(true);

        $this->assertFalse($authCode->isValid());
    }

    public function testVerifyCodeVerifierReturnsTrueWhenNoChallengeSet(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setCodeChallenge(null);

        $this->assertTrue($authCode->verifyCodeVerifier('any_verifier'));
    }

    public function testVerifyCodeVerifierReturnsTrueForPlainMethodWithMatchingVerifier(): void
    {
        $authCode = new AuthorizationCode();
        $verifier = 'test_verifier';
        $authCode->setCodeChallenge($verifier);
        $authCode->setCodeChallengeMethod('plain');

        $this->assertTrue($authCode->verifyCodeVerifier($verifier));
    }

    public function testVerifyCodeVerifierReturnsFalseForPlainMethodWithNonMatchingVerifier(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setCodeChallenge('correct_verifier');
        $authCode->setCodeChallengeMethod('plain');

        $this->assertFalse($authCode->verifyCodeVerifier('wrong_verifier'));
    }

    public function testVerifyCodeVerifierReturnsTrueForS256MethodWithCorrectVerifier(): void
    {
        $authCode = new AuthorizationCode();
        $verifier = 'test_verifier_string';

        // 生成正确的S256挑战
        $hash = hash('sha256', $verifier, true);
        $challenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        $authCode->setCodeChallenge($challenge);
        $authCode->setCodeChallengeMethod('S256');

        $this->assertTrue($authCode->verifyCodeVerifier($verifier));
    }

    public function testVerifyCodeVerifierReturnsFalseForS256MethodWithIncorrectVerifier(): void
    {
        $authCode = new AuthorizationCode();
        $correctVerifier = 'correct_verifier';

        // 生成基于正确验证器的挑战
        $hash = hash('sha256', $correctVerifier, true);
        $challenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        $authCode->setCodeChallenge($challenge);
        $authCode->setCodeChallengeMethod('S256');

        $this->assertFalse($authCode->verifyCodeVerifier('wrong_verifier'));
    }

    public function testVerifyCodeVerifierReturnsFalseForUnsupportedMethod(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setCodeChallenge('test_challenge');
        $authCode->setCodeChallengeMethod('unsupported_method');

        $this->assertFalse($authCode->verifyCodeVerifier('test_verifier'));
    }

    public function testCreateCreatesAuthCodeWithAllParameters(): void
    {
        $redirectUri = 'https://example.com/callback';
        $scopes = ['read', 'write'];
        $codeChallenge = 'test_challenge';
        $codeChallengeMethod = 'S256';
        $state = 'test_state';

        $authCode = AuthorizationCode::create(
            $this->mockClient,
            $this->mockUser,
            $redirectUri,
            $scopes,
            15,
            $codeChallenge,
            $codeChallengeMethod,
            $state
        );

        $this->assertSame($this->mockClient, $authCode->getClient());
        $this->assertSame($this->mockUser, $authCode->getUser());
        $this->assertSame($redirectUri, $authCode->getRedirectUri());
        $this->assertSame($scopes, $authCode->getScopes());
        $this->assertSame($codeChallenge, $authCode->getCodeChallenge());
        $this->assertSame($codeChallengeMethod, $authCode->getCodeChallengeMethod());
        $this->assertSame($state, $authCode->getState());
        $this->assertNotEmpty($authCode->getCode());
        $this->assertFalse($authCode->isUsed());

        // 验证过期时间大约在15分钟后
        $expectedExpiry = new \DateTimeImmutable('+15 minutes');
        $actualExpiry = $authCode->getExpireTime();
        $this->assertLessThan(60, abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()));
    }

    public function testCreateCreatesAuthCodeWithMinimalParameters(): void
    {
        $redirectUri = 'https://example.com/callback';

        $authCode = AuthorizationCode::create(
            $this->mockClient,
            $this->mockUser,
            $redirectUri
        );

        $this->assertSame($this->mockClient, $authCode->getClient());
        $this->assertSame($this->mockUser, $authCode->getUser());
        $this->assertSame($redirectUri, $authCode->getRedirectUri());
        $this->assertNull($authCode->getScopes());
        $this->assertNull($authCode->getCodeChallenge());
        $this->assertNull($authCode->getCodeChallengeMethod());
        $this->assertNull($authCode->getState());
        $this->assertNotEmpty($authCode->getCode());

        // 验证默认过期时间为10分钟
        $expectedExpiry = new \DateTimeImmutable('+10 minutes');
        $actualExpiry = $authCode->getExpireTime();
        $this->assertLessThan(60, abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()));
    }

    public function testCreateGeneratesUniqueCode(): void
    {
        $authCode1 = AuthorizationCode::create(
            $this->mockClient,
            $this->mockUser,
            'https://example.com/callback'
        );

        $authCode2 = AuthorizationCode::create(
            $this->mockClient,
            $this->mockUser,
            'https://example.com/callback'
        );

        $this->assertNotEquals($authCode1->getCode(), $authCode2->getCode());
    }

    public function testToStringReturnsCode(): void
    {
        $authCode = new AuthorizationCode();
        $code = 'test_code_value';
        $authCode->setCode($code);

        $this->assertSame($code, (string) $authCode);
    }

    public function testToStringReturnsEmptyStringWhenCodeEmpty(): void
    {
        $authCode = new AuthorizationCode();

        $this->assertSame('', (string) $authCode);
    }
}
