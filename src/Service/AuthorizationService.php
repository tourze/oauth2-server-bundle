<?php

namespace Tourze\OAuth2ServerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\AccessTokenBundle\Entity\AccessToken;
use Tourze\AccessTokenBundle\Service\AccessTokenService;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Exception\OAuth2Exception;
use Tourze\OAuth2ServerBundle\Repository\AuthorizationCodeRepository;

/**
 * OAuth2授权服务
 *
 * 处理各种OAuth2授权流程，包括客户端凭证授权和授权码模式
 */
class AuthorizationService
{
    public function __construct(
        private readonly OAuth2ClientService $clientService,
        private readonly AccessTokenService $accessTokenService,
        private readonly AuthorizationCodeRepository $authCodeRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 处理客户端凭证授权
     *
     * @param string     $clientId     客户端ID
     * @param string     $clientSecret 客户端密钥
     * @param array<string>|null $scopes       请求的作用域
     *
     * @throws OAuth2Exception
     */
    public function handleClientCredentialsGrant(
        string $clientId,
        string $clientSecret,
        ?array $scopes = null,
    ): AccessToken {
        // 验证客户端
        $client = $this->clientService->validateClient($clientId, $clientSecret);
        if (null === $client) {
            throw new OAuth2Exception('invalid_client', 'Invalid client credentials');
        }

        // 检查是否支持客户端凭证授权
        if (!$this->clientService->supportsGrantType($client, 'client_credentials')) {
            throw new OAuth2Exception('unauthorized_client', 'Client is not authorized for this grant type');
        }

        // 验证作用域
        $this->validateScopes($client, $scopes);

        // 创建访问令牌
        $clientUser = $client->getUser();
        if (null === $clientUser) {
            throw new OAuth2Exception('invalid_client', 'Client has no associated user');
        }

        return $this->accessTokenService->createToken(
            $clientUser,
            $client->getAccessTokenLifetime(),
            $this->generateDeviceInfo($client)
        );
    }

    /**
     * 生成授权码
     *
     * @param OAuth2Client  $client              OAuth2客户端
     * @param UserInterface $user                授权用户
     * @param string        $redirectUri         重定向URI
     * @param array<string>|null $scopes              授权作用域
     * @param string|null   $state               状态参数
     * @param string|null   $codeChallenge       PKCE代码挑战
     * @param string|null   $codeChallengeMethod PKCE代码挑战方法
     *
     * @throws OAuth2Exception
     */
    public function generateAuthorizationCode(
        OAuth2Client $client,
        UserInterface $user,
        string $redirectUri,
        ?array $scopes = null,
        ?string $state = null,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null,
    ): AuthorizationCode {
        // 验证重定向URI
        if (!$this->clientService->validateRedirectUri($client, $redirectUri)) {
            throw new OAuth2Exception('invalid_request', 'Invalid redirect URI');
        }

        // 检查是否支持授权码模式
        if (!$this->clientService->supportsGrantType($client, 'authorization_code')) {
            throw new OAuth2Exception('unauthorized_client', 'Client is not authorized for authorization code grant');
        }

        // 验证PKCE
        if (null !== $codeChallenge && !$this->validateCodeChallenge($client, $codeChallengeMethod)) {
            throw new OAuth2Exception('invalid_request', 'Invalid code challenge method');
        }

        // 验证作用域
        $validatedScopes = $this->validateScopes($client, $scopes);

        // 创建授权码
        $authCode = AuthorizationCode::create(
            $client,
            $user,
            $redirectUri,
            $validatedScopes,
            10, // 10分钟有效期
            $codeChallenge,
            $codeChallengeMethod,
            $state
        );

        $this->entityManager->persist($authCode);
        $this->entityManager->flush();

        return $authCode;
    }

    /**
     * 处理授权码换取访问令牌
     *
     * @param string      $code         授权码
     * @param string      $clientId     客户端ID
     * @param string|null $clientSecret 客户端密钥（机密客户端需要）
     * @param string      $redirectUri  重定向URI
     * @param string|null $codeVerifier PKCE代码验证器
     *
     * @throws OAuth2Exception
     */
    public function exchangeAuthorizationCode(
        string $code,
        string $clientId,
        ?string $clientSecret,
        string $redirectUri,
        ?string $codeVerifier = null,
    ): AccessToken {
        // 查找授权码
        $authCode = $this->authCodeRepository->findValidByCode($code);
        if (null === $authCode) {
            throw new OAuth2Exception('invalid_grant', 'Invalid authorization code');
        }

        // 验证客户端
        $client = $authCode->getClient();
        if (null === $client) {
            throw new OAuth2Exception('invalid_grant', 'Authorization code has no associated client');
        }

        if ($client->getClientId() !== $clientId) {
            throw new OAuth2Exception('invalid_client', 'Client mismatch');
        }

        // 机密客户端需要验证密钥
        if ($client->isConfidential()) {
            if (null === $clientSecret || !$this->clientService->verifyClientSecret($client, $clientSecret)) {
                throw new OAuth2Exception('invalid_client', 'Invalid client credentials');
            }
        }

        // 验证重定向URI
        if ($authCode->getRedirectUri() !== $redirectUri) {
            throw new OAuth2Exception('invalid_grant', 'Redirect URI mismatch');
        }

        // 验证PKCE
        if (!$authCode->verifyCodeVerifier($codeVerifier ?? '')) {
            throw new OAuth2Exception('invalid_grant', 'Invalid code verifier');
        }

        // 标记授权码为已使用
        $authCode->setUsed(true);
        $this->entityManager->persist($authCode);
        $this->entityManager->flush();

        // 创建访问令牌
        $authUser = $authCode->getUser();
        if (null === $authUser) {
            throw new OAuth2Exception('invalid_grant', 'Authorization code has no associated user');
        }

        return $this->accessTokenService->createToken(
            $authUser,
            $client->getAccessTokenLifetime(),
            $this->generateDeviceInfo($client)
        );
    }

    /**
     * 验证授权请求参数
     *
     * @param array<string>|null $scopes
     * @throws OAuth2Exception
     */
    public function validateAuthorizationRequest(
        string $clientId,
        string $responseType,
        string $redirectUri,
        ?array $scopes = null,
        ?string $state = null,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null,
    ): OAuth2Client {
        // 查找客户端
        $client = $this->clientService->validateClient($clientId);
        if (null === $client) {
            throw new OAuth2Exception('invalid_client', 'Invalid client');
        }

        // 验证响应类型
        if ('code' !== $responseType) {
            throw new OAuth2Exception('unsupported_response_type', 'Unsupported response type');
        }

        // 验证重定向URI
        if (!$this->clientService->validateRedirectUri($client, $redirectUri)) {
            throw new OAuth2Exception('invalid_request', 'Invalid redirect URI');
        }

        // 验证授权类型支持
        if (!$this->clientService->supportsGrantType($client, 'authorization_code')) {
            throw new OAuth2Exception('unauthorized_client', 'Client not authorized for authorization code grant');
        }

        // 验证PKCE
        if (null !== $codeChallenge && !$this->validateCodeChallenge($client, $codeChallengeMethod)) {
            throw new OAuth2Exception('invalid_request', 'Invalid code challenge method');
        }

        // 验证作用域
        $this->validateScopes($client, $scopes);

        return $client;
    }

    /**
     * 清理过期的授权码
     */
    public function cleanupExpiredAuthorizationCodes(): int
    {
        return $this->authCodeRepository->removeExpiredCodes();
    }

    /**
     * 验证作用域
     *
     * @param array<string>|null $requestedScopes
     * @return array<string>|null
     * @throws OAuth2Exception
     */
    private function validateScopes(OAuth2Client $client, ?array $requestedScopes): ?array
    {
        if (null === $requestedScopes) {
            return null;
        }

        $clientScopes = $client->getScopes();

        // 如果客户端没有限制作用域，允许所有请求的作用域
        if (null === $clientScopes) {
            return $requestedScopes;
        }

        // 检查请求的作用域是否都在客户端允许的范围内
        $invalidScopes = array_diff($requestedScopes, $clientScopes);
        if ([] !== $invalidScopes) {
            throw new OAuth2Exception('invalid_scope', 'Invalid scope: ' . implode(', ', $invalidScopes));
        }

        return $requestedScopes;
    }

    /**
     * 验证PKCE代码挑战方法
     */
    private function validateCodeChallenge(OAuth2Client $client, ?string $method): bool
    {
        if (null === $method) {
            $method = 'plain';
        }

        return $client->supportsCodeChallengeMethod($method);
    }

    /**
     * 生成设备信息
     */
    private function generateDeviceInfo(OAuth2Client $client): string
    {
        return sprintf('OAuth2 Client: %s (%s)', $client->getName(), $client->getClientId());
    }
}
