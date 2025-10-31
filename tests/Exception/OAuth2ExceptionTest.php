<?php

declare(strict_types=1);

namespace Tourze\OAuth2ServerBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OAuth2ServerBundle\Exception\OAuth2Exception;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * OAuth2Exception单元测试
 *
 * @internal
 */
#[CoversClass(OAuth2Exception::class)]
final class OAuth2ExceptionTest extends AbstractExceptionTestCase
{
    protected function setUpContainer(): void
    {
        // 这个测试不需要额外的设置
    }

    public function testConstructorWithAllParameters(): void
    {
        $error = 'invalid_request';
        $description = 'Test description';
        $uri = 'https://example.com/error';
        $statusCode = Response::HTTP_BAD_REQUEST;

        $exception = new OAuth2Exception($error, $description, $uri, $statusCode);

        $this->assertSame($error, $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame($uri, $exception->getErrorUri());
        $this->assertSame($statusCode, $exception->getHttpStatusCode());
        $this->assertSame($description, $exception->getMessage());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $error = 'invalid_client';

        $exception = new OAuth2Exception($error);

        $this->assertSame($error, $exception->getError());
        $this->assertSame('', $exception->getErrorDescription());
        $this->assertNull($exception->getErrorUri());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
        $this->assertSame($error, $exception->getMessage());
    }

    public function testConstructorWithEmptyDescription(): void
    {
        $error = 'server_error';
        $description = '';

        $exception = new OAuth2Exception($error, $description);

        $this->assertSame($error, $exception->getMessage());
    }

    public function testToArrayWithAllFields(): void
    {
        $exception = new OAuth2Exception(
            'invalid_grant',
            'Invalid authorization code',
            'https://example.com/docs/errors'
        );

        $result = $exception->toArray();

        $this->assertSame('invalid_grant', $result['error']);
        $this->assertSame('Invalid authorization code', $result['error_description']);
        $this->assertSame('https://example.com/docs/errors', $result['error_uri']);
    }

    public function testToArrayWithoutOptionalFields(): void
    {
        $exception = new OAuth2Exception('access_denied');

        $result = $exception->toArray();

        $this->assertSame('access_denied', $result['error']);
        $this->assertArrayNotHasKey('error_description', $result);
        $this->assertArrayNotHasKey('error_uri', $result);
    }

    public function testToArrayWithEmptyDescription(): void
    {
        $exception = new OAuth2Exception('invalid_scope', '');

        $result = $exception->toArray();

        $this->assertArrayNotHasKey('error_description', $result);
    }

    public function testInvalidRequestWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidRequest();

        $this->assertSame('invalid_request', $exception->getError());
        $this->assertSame('Invalid request', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function testInvalidRequestWithCustomDescription(): void
    {
        $description = 'Missing required parameter: client_id';
        $exception = OAuth2Exception::invalidRequest($description);

        $this->assertSame('invalid_request', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function testInvalidClientWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidClient();

        $this->assertSame('invalid_client', $exception->getError());
        $this->assertSame('Invalid client', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getHttpStatusCode());
    }

    public function testInvalidClientWithCustomDescription(): void
    {
        $description = 'Client authentication failed';
        $exception = OAuth2Exception::invalidClient($description);

        $this->assertSame('invalid_client', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getHttpStatusCode());
    }

    public function testInvalidGrantWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidGrant();

        $this->assertSame('invalid_grant', $exception->getError());
        $this->assertSame('Invalid grant', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function testInvalidGrantWithCustomDescription(): void
    {
        $description = 'Authorization code has expired';
        $exception = OAuth2Exception::invalidGrant($description);

        $this->assertSame('invalid_grant', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function testUnauthorizedClientWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::unauthorizedClient();

        $this->assertSame('unauthorized_client', $exception->getError());
        $this->assertSame('Unauthorized client', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function testUnauthorizedClientWithCustomDescription(): void
    {
        $description = 'Client not authorized for this grant type';
        $exception = OAuth2Exception::unauthorizedClient($description);

        $this->assertSame('unauthorized_client', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function testUnsupportedGrantTypeWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::unsupportedGrantType();

        $this->assertSame('unsupported_grant_type', $exception->getError());
        $this->assertSame('Unsupported grant type', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function testUnsupportedGrantTypeWithCustomDescription(): void
    {
        $description = 'Grant type "password" is not supported';
        $exception = OAuth2Exception::unsupportedGrantType($description);

        $this->assertSame('unsupported_grant_type', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function testUnsupportedResponseTypeWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::unsupportedResponseType();

        $this->assertSame('unsupported_response_type', $exception->getError());
        $this->assertSame('Unsupported response type', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function testUnsupportedResponseTypeWithCustomDescription(): void
    {
        $description = 'Response type "token" is not supported';
        $exception = OAuth2Exception::unsupportedResponseType($description);

        $this->assertSame('unsupported_response_type', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function testInvalidScopeWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidScope();

        $this->assertSame('invalid_scope', $exception->getError());
        $this->assertSame('Invalid scope', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function testInvalidScopeWithCustomDescription(): void
    {
        $description = 'Scope "admin" is not allowed';
        $exception = OAuth2Exception::invalidScope($description);

        $this->assertSame('invalid_scope', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function testAccessDeniedWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::accessDenied();

        $this->assertSame('access_denied', $exception->getError());
        $this->assertSame('Access denied', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getHttpStatusCode());
    }

    public function testAccessDeniedWithCustomDescription(): void
    {
        $description = 'User denied authorization request';
        $exception = OAuth2Exception::accessDenied($description);

        $this->assertSame('access_denied', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getHttpStatusCode());
    }

    public function testServerErrorWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::serverError();

        $this->assertSame('server_error', $exception->getError());
        $this->assertSame('Server error', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getHttpStatusCode());
    }

    public function testServerErrorWithCustomDescription(): void
    {
        $description = 'Database connection failed';
        $exception = OAuth2Exception::serverError($description);

        $this->assertSame('server_error', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getHttpStatusCode());
    }

    public function testTemporarilyUnavailableWithDefaultDescription(): void
    {
        $exception = OAuth2Exception::temporarilyUnavailable();

        $this->assertSame('temporarily_unavailable', $exception->getError());
        $this->assertSame('Temporarily unavailable', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $exception->getHttpStatusCode());
    }

    public function testTemporarilyUnavailableWithCustomDescription(): void
    {
        $description = 'Service is under maintenance';
        $exception = OAuth2Exception::temporarilyUnavailable($description);

        $this->assertSame('temporarily_unavailable', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $exception->getHttpStatusCode());
    }
}
