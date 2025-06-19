<?php

namespace Tourze\OAuth2ServerBundle\Tests\Entity;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

/**
 * AuthorizationCode实体单元测试
 */
class AuthorizationCodeTest extends TestCase
{
    private OAuth2Client&MockObject $mockClient;
    private UserInterface&MockObject $mockUser;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(OAuth2Client::class);
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->method('getUserIdentifier')->willReturn('test@example.com');
    }

    public function test_setCode_andGetCode(): void
    {
        $authCode = new AuthorizationCode();
        $code = 'test_authorization_code';

        $result = $authCode->setCode($code);

        $this->assertSame($authCode, $result);
        $this->assertSame($code, $authCode->getCode());
    }

    public function test_setClient_andGetClient(): void
    {
        $authCode = new AuthorizationCode();

        $result = $authCode->setClient($this->mockClient);

        $this->assertSame($authCode, $result);
        $this->assertSame($this->mockClient, $authCode->getClient());
    }

    public function test_setUser_andGetUser(): void
    {
        $authCode = new AuthorizationCode();

        $result = $authCode->setUser($this->mockUser);

        $this->assertSame($authCode, $result);
        $this->assertSame($this->mockUser, $authCode->getUser());
    }

    public function test_setRedirectUri_andGetRedirectUri(): void
    {
        $authCode = new AuthorizationCode();
        $uri = 'https://example.com/callback';

        $result = $authCode->setRedirectUri($uri);

        $this->assertSame($authCode, $result);
        $this->assertSame($uri, $authCode->getRedirectUri());
    }

    public function test_setExpiresAt_andGetExpiresAt(): void
    {
        $authCode = new AuthorizationCode();
        $expiresAt = new \DateTimeImmutable('+10 minutes');

        $result = $authCode->setExpiresAt($expiresAt);

        $this->assertSame($authCode, $result);
        $this->assertSame($expiresAt, $authCode->getExpiresAt());
    }

    public function test_setScopes_andGetScopes(): void
    {
        $authCode = new AuthorizationCode();
        $scopes = ['read', 'write'];

        $result = $authCode->setScopes($scopes);

        $this->assertSame($authCode, $result);
        $this->assertSame($scopes, $authCode->getScopes());
    }

    public function test_setCodeChallenge_andGetCodeChallenge(): void
    {
        $authCode = new AuthorizationCode();
        $challenge = 'test_challenge';

        $result = $authCode->setCodeChallenge($challenge);

        $this->assertSame($authCode, $result);
        $this->assertSame($challenge, $authCode->getCodeChallenge());
    }

    public function test_setCodeChallengeMethod_andGetCodeChallengeMethod(): void
    {
        $authCode = new AuthorizationCode();
        $method = 'S256';

        $result = $authCode->setCodeChallengeMethod($method);

        $this->assertSame($authCode, $result);
        $this->assertSame($method, $authCode->getCodeChallengeMethod());
    }

    public function test_setUsed_andIsUsed(): void
    {
        $authCode = new AuthorizationCode();

        $this->assertFalse($authCode->isUsed());

        $result = $authCode->setUsed(true);

        $this->assertSame($authCode, $result);
        $this->assertTrue($authCode->isUsed());
    }

    public function test_setState_andGetState(): void
    {
        $authCode = new AuthorizationCode();
        $state = 'random_state_value';

        $result = $authCode->setState($state);

        $this->assertSame($authCode, $result);
        $this->assertSame($state, $authCode->getState());
    }

    public function test_isExpired_returnsFalseForFutureDate(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpiresAt(new \DateTimeImmutable('+10 minutes'));

        $this->assertFalse($authCode->isExpired());
    }

    public function test_isExpired_returnsTrueForPastDate(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpiresAt(new \DateTimeImmutable('-1 minute'));

        $this->assertTrue($authCode->isExpired());
    }

    public function test_isValid_returnsTrueWhenNotExpiredAndNotUsed(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpiresAt(new \DateTimeImmutable('+10 minutes'));
        $authCode->setUsed(false);

        $this->assertTrue($authCode->isValid());
    }

    public function test_isValid_returnsFalseWhenExpired(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpiresAt(new \DateTimeImmutable('-1 minute'));
        $authCode->setUsed(false);

        $this->assertFalse($authCode->isValid());
    }

    public function test_isValid_returnsFalseWhenUsed(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setExpiresAt(new \DateTimeImmutable('+10 minutes'));
        $authCode->setUsed(true);

        $this->assertFalse($authCode->isValid());
    }

    public function test_verifyCodeVerifier_returnsTrueWhenNoChallengeSet(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setCodeChallenge(null);

        $this->assertTrue($authCode->verifyCodeVerifier('any_verifier'));
    }

    public function test_verifyCodeVerifier_returnsTrueForPlainMethodWithMatchingVerifier(): void
    {
        $authCode = new AuthorizationCode();
        $verifier = 'test_verifier';
        $authCode->setCodeChallenge($verifier);
        $authCode->setCodeChallengeMethod('plain');

        $this->assertTrue($authCode->verifyCodeVerifier($verifier));
    }

    public function test_verifyCodeVerifier_returnsFalseForPlainMethodWithNonMatchingVerifier(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setCodeChallenge('correct_verifier');
        $authCode->setCodeChallengeMethod('plain');

        $this->assertFalse($authCode->verifyCodeVerifier('wrong_verifier'));
    }

    public function test_verifyCodeVerifier_returnsTrueForS256MethodWithCorrectVerifier(): void
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

    public function test_verifyCodeVerifier_returnsFalseForS256MethodWithIncorrectVerifier(): void
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

    public function test_verifyCodeVerifier_returnsFalseForUnsupportedMethod(): void
    {
        $authCode = new AuthorizationCode();
        $authCode->setCodeChallenge('test_challenge');
        $authCode->setCodeChallengeMethod('unsupported_method');

        $this->assertFalse($authCode->verifyCodeVerifier('test_verifier'));
    }

    public function test_create_createsAuthCodeWithAllParameters(): void
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

        $this->assertInstanceOf(AuthorizationCode::class, $authCode);
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
        $actualExpiry = $authCode->getExpiresAt();
        $this->assertLessThan(60, abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()));
    }

    public function test_create_createsAuthCodeWithMinimalParameters(): void
    {
        $redirectUri = 'https://example.com/callback';

        $authCode = AuthorizationCode::create(
            $this->mockClient,
            $this->mockUser,
            $redirectUri
        );

        $this->assertInstanceOf(AuthorizationCode::class, $authCode);
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
        $actualExpiry = $authCode->getExpiresAt();
        $this->assertLessThan(60, abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()));
    }

    public function test_create_generatesUniqueCode(): void
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

    public function test_toString_returnsCode(): void
    {
        $authCode = new AuthorizationCode();
        $code = 'test_code_value';
        $authCode->setCode($code);

        $this->assertSame($code, (string)$authCode);
    }

    public function test_toString_returnsEmptyStringWhenCodeEmpty(): void
    {
        $authCode = new AuthorizationCode();

        $this->assertSame('', (string)$authCode);
    }

    public function test_setCreateTime_andGetCreateTime(): void
    {
        $authCode = new AuthorizationCode();
        $time = new \DateTimeImmutable();

        $result = $authCode->setCreateTime($time);

        $this->assertSame($authCode, $result);
        $this->assertSame($time, $authCode->getCreateTime());
    }

    public function test_setCreateTime_withNull(): void
    {
        $authCode = new AuthorizationCode();

        $result = $authCode->setCreateTime(null);

        $this->assertSame($authCode, $result);
        $this->assertNull($authCode->getCreateTime());
    }
}
