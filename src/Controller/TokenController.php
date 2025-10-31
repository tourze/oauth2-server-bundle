<?php

namespace Tourze\OAuth2ServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Exception\OAuth2Exception;
use Tourze\OAuth2ServerBundle\Service\AccessLogService;
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

/**
 * OAuth2令牌端点控制器
 *
 * @see https://tools.ietf.org/html/rfc6749#section-3.2
 */
#[Autoconfigure(public: true)]
final class TokenController extends AbstractController
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly AccessLogService $accessLogService,
    ) {
    }

    /**
     * 令牌端点 - 处理各种授权类型的令牌请求
     */
    #[Route(path: '/oauth2/token', name: 'oauth2_token', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $grantType = $request->request->get('grant_type');
            $response = match ($grantType) {
                'client_credentials' => $this->processClientCredentialsGrant($request),
                'authorization_code' => $this->processAuthorizationCodeGrant($request),
                default => throw OAuth2Exception::unsupportedGrantType('Unsupported grant type: ' . $grantType),
            };

            $this->logSuccess('token', $request, $startTime);

            return $response;
        } catch (OAuth2Exception $e) {
            $this->logError('token', $request, $e, $startTime);

            return $this->createErrorResponse($e);
        } catch (\Throwable $e) {
            $oauth2Exception = OAuth2Exception::serverError('Internal server error');
            $this->logError('token', $request, $oauth2Exception, $startTime);

            return $this->createErrorResponse($oauth2Exception);
        }
    }

    /**
     * 处理客户端凭证授权
     */
    private function processClientCredentialsGrant(Request $request): JsonResponse
    {
        $credentials = $this->extractClientCredentials($request);
        $scopes = $this->extractScopes($request);

        $accessToken = $this->authorizationService->handleClientCredentialsGrant(
            $credentials['client_id'],
            $credentials['client_secret'],
            $scopes
        );

        return $this->createTokenResponse($accessToken);
    }

    /**
     * 处理授权码授权
     */
    private function processAuthorizationCodeGrant(Request $request): JsonResponse
    {
        $credentials = $this->extractClientCredentials($request);
        $authCodeData = $this->extractAuthorizationCodeData($request);

        // 验证必需参数
        if (null === $authCodeData['code'] || '' === $authCodeData['code']) {
            throw OAuth2Exception::invalidRequest('Authorization code is required');
        }

        if (null === $authCodeData['redirect_uri'] || '' === $authCodeData['redirect_uri']) {
            throw OAuth2Exception::invalidRequest('Redirect URI is required');
        }

        $accessToken = $this->authorizationService->exchangeAuthorizationCode(
            $authCodeData['code'],
            $credentials['client_id'],
            $credentials['client_secret'],
            $authCodeData['redirect_uri'],
            $authCodeData['code_verifier']
        );

        return $this->createTokenResponse($accessToken);
    }

    /**
     * 提取客户端凭证
     *
     * @return array{client_id: string, client_secret: string}
     */
    private function extractClientCredentials(Request $request): array
    {
        $clientId = $request->request->get('client_id');
        $clientSecret = $request->request->get('client_secret');

        // 确保是字符串类型
        $clientId = is_string($clientId) ? $clientId : null;
        $clientSecret = is_string($clientSecret) ? $clientSecret : null;

        if (null === $clientId || '' === $clientId || null === $clientSecret || '' === $clientSecret) {
            [$clientId, $clientSecret] = $this->extractCredentialsFromHeader($request);
        }

        $this->validateClientCredentials($clientId, $clientSecret);

        // 经过验证后确保非null
        assert(is_string($clientId) && is_string($clientSecret));

        return ['client_id' => $clientId, 'client_secret' => $clientSecret];
    }

    /**
     * 从 Authorization 头部提取凭证
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function extractCredentialsFromHeader(Request $request): array
    {
        $authorization = $request->headers->get('Authorization');

        if (null === $authorization || !str_starts_with($authorization, 'Basic ')) {
            return [null, null];
        }

        $credentials = base64_decode(substr($authorization, 6), true);

        if (false === $credentials || !str_contains($credentials, ':')) {
            return [null, null];
        }

        $parts = explode(':', $credentials, 2);

        return [
            $parts[0],
            $parts[1] ?? null,
        ];
    }

    /**
     * 验证客户端凭证
     */
    private function validateClientCredentials(?string $clientId, ?string $clientSecret): void
    {
        if (null === $clientId || '' === $clientId || null === $clientSecret || '' === $clientSecret) {
            throw OAuth2Exception::invalidRequest('client_id and client_secret are required');
        }
    }

    /**
     * 提取作用域
     *
     * @return array<string>|null
     */
    private function extractScopes(Request $request): ?array
    {
        $scope = $request->request->get('scope');

        if (null === $scope || '' === $scope) {
            return null;
        }

        if (!is_string($scope)) {
            return null;
        }

        return explode(' ', $scope);
    }

    /**
     * 提取授权码相关数据
     *
     * @return array{code: string|null, redirect_uri: string|null, code_verifier: string|null}
     */
    private function extractAuthorizationCodeData(Request $request): array
    {
        $code = $request->request->get('code');
        $redirectUri = $request->request->get('redirect_uri');
        $codeVerifier = $request->request->get('code_verifier');

        return [
            'code' => is_string($code) ? $code : null,
            'redirect_uri' => is_string($redirectUri) ? $redirectUri : null,
            'code_verifier' => is_string($codeVerifier) ? $codeVerifier : null,
        ];
    }

    /**
     * 创建令牌响应
     * @param mixed $accessToken
     */
    private function createTokenResponse(mixed $accessToken): JsonResponse
    {
        assert(is_object($accessToken) && method_exists($accessToken, 'getToken') && method_exists($accessToken, 'getExpiresAt'));
        $expiresAt = $accessToken->getExpiresAt();
        assert($expiresAt instanceof \DateTimeInterface);

        return new JsonResponse([
            'access_token' => $accessToken->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt->getTimestamp() - time(),
        ]);
    }

    /**
     * 记录成功访问日志
     */
    private function logSuccess(
        string $endpoint,
        Request $request,
        float $startTime,
        ?OAuth2Client $client = null,
        ?UserInterface $user = null,
    ): void {
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);
        $this->accessLogService->logSuccess($endpoint, $request, $client, $user, $responseTime);
    }

    /**
     * 记录错误访问日志
     */
    private function logError(
        string $endpoint,
        Request $request,
        OAuth2Exception $exception,
        float $startTime,
        ?OAuth2Client $client = null,
        ?UserInterface $user = null,
    ): void {
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);
        $this->accessLogService->logError(
            $endpoint,
            $request,
            $exception->getError(),
            $exception->getErrorDescription(),
            $client,
            $user,
            $responseTime
        );
    }

    /**
     * 创建错误响应
     */
    private function createErrorResponse(OAuth2Exception $exception): JsonResponse
    {
        return new JsonResponse([
            'error' => $exception->getError(),
            'error_description' => $exception->getErrorDescription(),
        ], $exception->getHttpStatusCode());
    }
}
