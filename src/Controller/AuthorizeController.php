<?php

namespace Tourze\OAuth2ServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Exception\OAuth2Exception;
use Tourze\OAuth2ServerBundle\Service\AccessLogService;
use Tourze\OAuth2ServerBundle\Service\AuthorizationService;

/**
 * OAuth2授权端点控制器
 *
 * @see https://tools.ietf.org/html/rfc6749#section-3.1
 */
class AuthorizeController extends AbstractController
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly AccessLogService $accessLogService
    ) {
    }

    /**
     * 授权端点 - 处理授权码模式的授权请求
     */
    #[Route(path: '/oauth2/authorize', name: 'oauth2_authorize', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, #[CurrentUser] ?UserInterface $user = null): Response
    {
        $startTime = microtime(true);

        try {
            $authRequest = $this->validateAuthorizationParameters($request);
            $client = $authRequest['client'];

            if ($user === null) {
                return $this->redirectToLogin($request);
            }

            if ($request->isMethod('POST')) {
                $response = $this->processUserAuthorization($request, $user, $authRequest);
            } else {
                $response = $this->renderAuthorizationPage($authRequest, $user);
            }

            $this->logSuccess('authorize', $request, $startTime, $client, $user);
            return $response;

        } catch (OAuth2Exception $e) {
            $this->logError('authorize', $request, $e, $startTime);
            return $this->handleAuthorizationError($e, $request);
        }
    }

    /**
     * 验证授权请求参数
     */
    private function validateAuthorizationParameters(Request $request): array
    {
        $clientId = $request->query->get('client_id');
        $responseType = $request->query->get('response_type');
        $redirectUri = $request->query->get('redirect_uri');
        $scopes = $request->query->get('scope') ? explode(' ', $request->query->get('scope')) : null;
        $state = $request->query->get('state');
        $codeChallenge = $request->query->get('code_challenge');
        $codeChallengeMethod = $request->query->get('code_challenge_method');

        $client = $this->authorizationService->validateAuthorizationRequest(
            $clientId,
            $responseType,
            $redirectUri,
            $scopes,
            $state,
            $codeChallenge,
            $codeChallengeMethod
        );

        return [
            'client' => $client,
            'response_type' => $responseType,
            'redirect_uri' => $redirectUri,
            'scopes' => $scopes,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
        ];
    }

    /**
     * 处理用户授权决定
     */
    private function processUserAuthorization(Request $request, UserInterface $user, array $authRequest): Response
    {
        if ($request->request->get('authorize') !== 'yes') {
            return $this->redirectWithError(
                $authRequest['redirect_uri'], 
                'access_denied', 
                'User denied authorization', 
                $authRequest['state']
            );
        }

        $authCode = $this->authorizationService->generateAuthorizationCode(
            $authRequest['client'],
            $user,
            $authRequest['redirect_uri'],
            $authRequest['scopes'],
            $authRequest['state'],
            $authRequest['code_challenge'],
            $authRequest['code_challenge_method']
        );

        return $this->redirectWithAuthorizationCode(
            $authRequest['redirect_uri'], 
            $authCode->getCode(), 
            $authRequest['state']
        );
    }

    /**
     * 渲染授权页面
     */
    private function renderAuthorizationPage(array $authRequest, UserInterface $user): Response
    {
        return $this->render('@OAuth2Server/authorize.html.twig', [
            'client' => $authRequest['client'],
            'scopes' => $authRequest['scopes'],
            'redirectUri' => $authRequest['redirect_uri'],
            'state' => $authRequest['state'],
            'user' => $user,
        ]);
    }

    /**
     * 重定向到登录页面
     */
    private function redirectToLogin(Request $request): Response
    {
        return $this->redirectToRoute('app_login', [
            'redirect_uri' => $request->getUri()
        ]);
    }

    /**
     * 带授权码重定向
     */
    private function redirectWithAuthorizationCode(string $redirectUri, string $code, ?string $state): Response
    {
        $params = ['code' => $code];
        if ($state !== null) {
            $params['state'] = $state;
        }

        $redirectUrl = $redirectUri . '?' . http_build_query($params);
        return $this->redirect($redirectUrl);
    }

    /**
     * 带错误信息重定向
     */
    private function redirectWithError(string $redirectUri, string $error, string $errorDescription, ?string $state): Response
    {
        $params = [
            'error' => $error,
            'error_description' => $errorDescription,
        ];

        if ($state !== null) {
            $params['state'] = $state;
        }

        $redirectUrl = $redirectUri . '?' . http_build_query($params);
        return $this->redirect($redirectUrl);
    }

    /**
     * 处理授权错误
     */
    private function handleAuthorizationError(OAuth2Exception $e, Request $request): Response
    {
        $redirectUri = $request->query->get('redirect_uri');
        $state = $request->query->get('state');

        if ($redirectUri && $this->isValidRedirectUri($redirectUri)) {
            return $this->redirectWithError($redirectUri, $e->getError(), $e->getErrorDescription(), $state);
        }

        return $this->render('@OAuth2Server/error.html.twig', [
            'error' => $e->getError(),
            'error_description' => $e->getErrorDescription(),
        ], new Response('', $e->getHttpStatusCode()));
    }

    /**
     * 记录成功访问日志
     */
    private function logSuccess(
        string $endpoint, 
        Request $request, 
        float $startTime, 
        ?OAuth2Client $client = null, 
        ?UserInterface $user = null
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
        ?UserInterface $user = null
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
     * 验证重定向URI
     */
    private function isValidRedirectUri(string $uri): bool
    {
        return filter_var($uri, FILTER_VALIDATE_URL) !== false;
    }
}