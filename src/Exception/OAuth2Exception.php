<?php

namespace Tourze\OAuth2ServerBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * OAuth2异常类
 * 
 * 用于表示OAuth2流程中的各种错误，符合RFC 6749标准
 */
class OAuth2Exception extends \Exception
{
    private string $error;
    private string $errorDescription;
    private ?string $errorUri;
    private int $httpStatusCode;

    public function __construct(
        string $error,
        string $errorDescription = '',
        ?string $errorUri = null,
        int $httpStatusCode = Response::HTTP_BAD_REQUEST,
        ?\Throwable $previous = null
    ) {
        $this->error = $error;
        $this->errorDescription = $errorDescription;
        $this->errorUri = $errorUri;
        $this->httpStatusCode = $httpStatusCode;

        parent::__construct($errorDescription ?: $error, 0, $previous);
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getErrorDescription(): string
    {
        return $this->errorDescription;
    }

    public function getErrorUri(): ?string
    {
        return $this->errorUri;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * 将异常转换为数组格式（用于JSON响应）
     */
    public function toArray(): array
    {
        $result = [
            'error' => $this->error,
        ];

        if ($this->errorDescription !== '') {
            $result['error_description'] = $this->errorDescription;
        }

        if ($this->errorUri !== null) {
            $result['error_uri'] = $this->errorUri;
        }

        return $result;
    }

    /**
     * 创建"无效请求"异常
     */
    public static function invalidRequest(string $description = 'Invalid request'): self
    {
        return new self('invalid_request', $description);
    }

    /**
     * 创建"无效客户端"异常
     */
    public static function invalidClient(string $description = 'Invalid client'): self
    {
        return new self('invalid_client', $description, null, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 创建"无效授权"异常
     */
    public static function invalidGrant(string $description = 'Invalid grant'): self
    {
        return new self('invalid_grant', $description);
    }

    /**
     * 创建"未授权客户端"异常
     */
    public static function unauthorizedClient(string $description = 'Unauthorized client'): self
    {
        return new self('unauthorized_client', $description);
    }

    /**
     * 创建"不支持的授权类型"异常
     */
    public static function unsupportedGrantType(string $description = 'Unsupported grant type'): self
    {
        return new self('unsupported_grant_type', $description);
    }

    /**
     * 创建"不支持的响应类型"异常
     */
    public static function unsupportedResponseType(string $description = 'Unsupported response type'): self
    {
        return new self('unsupported_response_type', $description);
    }

    /**
     * 创建"无效作用域"异常
     */
    public static function invalidScope(string $description = 'Invalid scope'): self
    {
        return new self('invalid_scope', $description);
    }

    /**
     * 创建"访问被拒绝"异常
     */
    public static function accessDenied(string $description = 'Access denied'): self
    {
        return new self('access_denied', $description, null, Response::HTTP_FORBIDDEN);
    }

    /**
     * 创建"服务器错误"异常
     */
    public static function serverError(string $description = 'Server error'): self
    {
        return new self('server_error', $description, null, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 创建"暂时不可用"异常
     */
    public static function temporarilyUnavailable(string $description = 'Temporarily unavailable'): self
    {
        return new self('temporarily_unavailable', $description, null, Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
