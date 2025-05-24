<?php

namespace Tourze\OAuth2ServerBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OAuth2ServerBundle\Exception\OAuth2Exception;

/**
 * OAuth2Exception单元测试
 */
class OAuth2ExceptionTest extends TestCase
{
    public function test_constructor_withAllParameters(): void
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

    public function test_constructor_withMinimalParameters(): void
    {
        $error = 'invalid_client';
        
        $exception = new OAuth2Exception($error);
        
        $this->assertSame($error, $exception->getError());
        $this->assertSame('', $exception->getErrorDescription());
        $this->assertNull($exception->getErrorUri());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
        $this->assertSame($error, $exception->getMessage());
    }

    public function test_constructor_withEmptyDescription(): void
    {
        $error = 'server_error';
        $description = '';
        
        $exception = new OAuth2Exception($error, $description);
        
        $this->assertSame($error, $exception->getMessage());
    }

    public function test_toArray_withAllFields(): void
    {
        $exception = new OAuth2Exception(
            'invalid_grant',
            'Invalid authorization code',
            'https://example.com/docs/errors'
        );
        
        $result = $exception->toArray();
        
        $this->assertIsArray($result);
        $this->assertSame('invalid_grant', $result['error']);
        $this->assertSame('Invalid authorization code', $result['error_description']);
        $this->assertSame('https://example.com/docs/errors', $result['error_uri']);
    }

    public function test_toArray_withoutOptionalFields(): void
    {
        $exception = new OAuth2Exception('access_denied');
        
        $result = $exception->toArray();
        
        $this->assertIsArray($result);
        $this->assertSame('access_denied', $result['error']);
        $this->assertArrayNotHasKey('error_description', $result);
        $this->assertArrayNotHasKey('error_uri', $result);
    }

    public function test_toArray_withEmptyDescription(): void
    {
        $exception = new OAuth2Exception('invalid_scope', '');
        
        $result = $exception->toArray();
        
        $this->assertArrayNotHasKey('error_description', $result);
    }

    public function test_invalidRequest_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidRequest();
        
        $this->assertSame('invalid_request', $exception->getError());
        $this->assertSame('Invalid request', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function test_invalidRequest_withCustomDescription(): void
    {
        $description = 'Missing required parameter: client_id';
        $exception = OAuth2Exception::invalidRequest($description);
        
        $this->assertSame('invalid_request', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function test_invalidClient_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidClient();
        
        $this->assertSame('invalid_client', $exception->getError());
        $this->assertSame('Invalid client', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getHttpStatusCode());
    }

    public function test_invalidClient_withCustomDescription(): void
    {
        $description = 'Client authentication failed';
        $exception = OAuth2Exception::invalidClient($description);
        
        $this->assertSame('invalid_client', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $exception->getHttpStatusCode());
    }

    public function test_invalidGrant_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidGrant();
        
        $this->assertSame('invalid_grant', $exception->getError());
        $this->assertSame('Invalid grant', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function test_invalidGrant_withCustomDescription(): void
    {
        $description = 'Authorization code has expired';
        $exception = OAuth2Exception::invalidGrant($description);
        
        $this->assertSame('invalid_grant', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function test_unauthorizedClient_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::unauthorizedClient();
        
        $this->assertSame('unauthorized_client', $exception->getError());
        $this->assertSame('Unauthorized client', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function test_unauthorizedClient_withCustomDescription(): void
    {
        $description = 'Client not authorized for this grant type';
        $exception = OAuth2Exception::unauthorizedClient($description);
        
        $this->assertSame('unauthorized_client', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function test_unsupportedGrantType_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::unsupportedGrantType();
        
        $this->assertSame('unsupported_grant_type', $exception->getError());
        $this->assertSame('Unsupported grant type', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function test_unsupportedGrantType_withCustomDescription(): void
    {
        $description = 'Grant type "password" is not supported';
        $exception = OAuth2Exception::unsupportedGrantType($description);
        
        $this->assertSame('unsupported_grant_type', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function test_unsupportedResponseType_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::unsupportedResponseType();
        
        $this->assertSame('unsupported_response_type', $exception->getError());
        $this->assertSame('Unsupported response type', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function test_unsupportedResponseType_withCustomDescription(): void
    {
        $description = 'Response type "token" is not supported';
        $exception = OAuth2Exception::unsupportedResponseType($description);
        
        $this->assertSame('unsupported_response_type', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function test_invalidScope_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::invalidScope();
        
        $this->assertSame('invalid_scope', $exception->getError());
        $this->assertSame('Invalid scope', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getHttpStatusCode());
    }

    public function test_invalidScope_withCustomDescription(): void
    {
        $description = 'Scope "admin" is not allowed';
        $exception = OAuth2Exception::invalidScope($description);
        
        $this->assertSame('invalid_scope', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
    }

    public function test_accessDenied_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::accessDenied();
        
        $this->assertSame('access_denied', $exception->getError());
        $this->assertSame('Access denied', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getHttpStatusCode());
    }

    public function test_accessDenied_withCustomDescription(): void
    {
        $description = 'User denied authorization request';
        $exception = OAuth2Exception::accessDenied($description);
        
        $this->assertSame('access_denied', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_FORBIDDEN, $exception->getHttpStatusCode());
    }

    public function test_serverError_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::serverError();
        
        $this->assertSame('server_error', $exception->getError());
        $this->assertSame('Server error', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getHttpStatusCode());
    }

    public function test_serverError_withCustomDescription(): void
    {
        $description = 'Database connection failed';
        $exception = OAuth2Exception::serverError($description);
        
        $this->assertSame('server_error', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getHttpStatusCode());
    }

    public function test_temporarilyUnavailable_withDefaultDescription(): void
    {
        $exception = OAuth2Exception::temporarilyUnavailable();
        
        $this->assertSame('temporarily_unavailable', $exception->getError());
        $this->assertSame('Temporarily unavailable', $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $exception->getHttpStatusCode());
    }

    public function test_temporarilyUnavailable_withCustomDescription(): void
    {
        $description = 'Service is under maintenance';
        $exception = OAuth2Exception::temporarilyUnavailable($description);
        
        $this->assertSame('temporarily_unavailable', $exception->getError());
        $this->assertSame($description, $exception->getErrorDescription());
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $exception->getHttpStatusCode());
    }
} 