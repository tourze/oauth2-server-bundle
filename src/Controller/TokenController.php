<?php

namespace Tourze\OAuth2ServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Exception\OAuth2Exception;
use Tourze\OAuth2ServerBundle\Service\AccessLogService;
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

/**
 * OAuth2令牌端点控制器
 * 
 * @see https://tools.ietf.org/html/rfc6749#section-3.2
 */
class TokenController extends AbstractController
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly AccessLogService $accessLogService
    ) {
    }

    /**
     * 令牌端点 - 处理各种授权类型的令牌请求
     */
    #[Route('/oauth2/token', name: 'oauth2_token', methods: ['POST'])]
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
     */
    private function extractClientCredentials(Request $request): array
    {
        $clientId = $request->request->get('client_id');
        $clientSecret = $request->request->get('client_secret');

        if (!$clientId || !$clientSecret) {
            $authorization = $request->headers->get('Authorization');
            if ($authorization !== null && str_starts_with($authorization, 'Basic ')) {
                $credentials = base64_decode(substr($authorization, 6));
                if ($credentials !== false && str_contains($credentials, ':')) {
                    [$clientId, $clientSecret] = explode(':', $credentials, 2);
                }
            }
        }

        if (!$clientId || !$clientSecret) {
            throw OAuth2Exception::invalidRequest('client_id and client_secret are required');
        }

        return ['client_id' => $clientId, 'client_secret' => $clientSecret];
    }

    /**
     * 提取作用域
     */
    private function extractScopes(Request $request): ?array
    {
        $scope = $request->request->get('scope');
        return $scope ? explode(' ', $scope) : null;
    }

    /**
     * 提取授权码相关数据
     */
    private function extractAuthorizationCodeData(Request $request): array
    {
        return [
            'code' => $request->request->get('code'),
            'redirect_uri' => $request->request->get('redirect_uri'),
            'code_verifier' => $request->request->get('code_verifier'),
        ];
    }

    /**
     * 创建令牌响应
     */
    private function createTokenResponse($accessToken): JsonResponse
    {
        return new JsonResponse([
            'access_token' => $accessToken->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $accessToken->getExpiresAt()->getTimestamp() - time(),
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
        ?\Symfony\Component\Security\Core\User\UserInterface $user = null
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
        ?\Symfony\Component\Security\Core\User\UserInterface $user = null
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