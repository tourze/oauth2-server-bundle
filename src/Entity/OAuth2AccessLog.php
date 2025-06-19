<?php

namespace Tourze\OAuth2ServerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\OAuth2ServerBundle\Repository\OAuth2AccessLogRepository;

/**
 * OAuth2访问日志实体
 *
 * 记录OAuth2端点的访问情况，包括令牌端点和授权端点的访问日志，
 * 用于安全审计、异常检测和统计分析
 */
#[ORM\Entity(repositoryClass: OAuth2AccessLogRepository::class)]
#[ORM\Table(name: 'oauth2_access_log', options: ['comment' => 'OAuth2访问日志'])]
#[ORM\Index(name: 'idx_endpoint', columns: ['endpoint'])]
#[ORM\Index(name: 'idx_client_id', columns: ['client_id'])]
#[ORM\Index(name: 'idx_ip_address', columns: ['ip_address'])]
#[ORM\Index(name: 'idx_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class OAuth2AccessLog implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '端点名称'])]
    private string $endpoint;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '客户端ID'])]
    private ?string $clientId = null;

    #[ORM\ManyToOne(targetEntity: OAuth2Client::class)]
    #[ORM\JoinColumn(name: 'oauth2_client_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OAuth2Client $client = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID'])]
    private ?string $userId = null;

    #[ORM\Column(type: Types::STRING, length: 45, options: ['comment' => 'IP地址'])]
    private string $ipAddress;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '用户代理'])]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => 'HTTP方法'])]
    private string $method;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '请求参数'])]
    private ?array $requestParams = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '状态'])]
    private string $status;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '错误代码'])]
    private ?string $errorCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误消息'])]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '响应时间(毫秒)'])]
    private ?int $responseTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * 创建访问日志记录
     */
    public static function create(
        string $endpoint,
        string $ipAddress,
        string $method,
        string $status,
        ?string $clientId = null,
        ?OAuth2Client $client = null,
        ?string $userId = null,
        ?string $userAgent = null,
        ?array $requestParams = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?int $responseTime = null
    ): self {
        $log = new self();
        $log->endpoint = $endpoint;
        $log->ipAddress = $ipAddress;
        $log->method = $method;
        $log->status = $status;
        $log->clientId = $clientId;
        $log->client = $client;
        $log->userId = $userId;
        $log->userAgent = $userAgent;
        $log->requestParams = $requestParams;
        $log->errorCode = $errorCode;
        $log->errorMessage = $errorMessage;
        $log->responseTime = $responseTime;

        return $log;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClient(): ?OAuth2Client
    {
        return $this->client;
    }

    public function setClient(?OAuth2Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getRequestParams(): ?array
    {
        return $this->requestParams;
    }

    public function setRequestParams(?array $requestParams): self
    {
        $this->requestParams = $requestParams;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getResponseTime(): ?int
    {
        return $this->responseTime;
    }

    public function setResponseTime(?int $responseTime): self
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * 检查是否为成功状态
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * 检查是否为错误状态
     */
    public function isError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * 获取格式化的响应时间
     */
    public function getFormattedResponseTime(): string
    {
        if ($this->responseTime === null) {
            return 'N/A';
        }

        return $this->responseTime . 'ms';
    }

    public function __toString(): string
    {
        return "{$this->endpoint} [{$this->status}] {$this->ipAddress}";
    }
}
