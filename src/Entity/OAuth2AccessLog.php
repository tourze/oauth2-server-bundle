<?php

namespace Tourze\OAuth2ServerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tourze\OAuth2ServerBundle\Repository\OAuth2AccessLogRepository;

/**
 * OAuth2访问日志实体
 *
 * 记录OAuth2端点的访问情况，包括令牌端点和授权端点的访问日志，
 * 用于安全审计、异常检测和统计分析
 */
#[ORM\Entity(repositoryClass: OAuth2AccessLogRepository::class)]
#[ORM\Table(name: 'oauth2_access_log')]
#[ORM\Index(name: 'idx_endpoint', columns: ['endpoint'])]
#[ORM\Index(name: 'idx_client_id', columns: ['client_id'])]
#[ORM\Index(name: 'idx_ip_address', columns: ['ip_address'])]
#[ORM\Index(name: 'idx_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class OAuth2AccessLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $endpoint;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $clientId = null;

    #[ORM\ManyToOne(targetEntity: OAuth2Client::class)]
    #[ORM\JoinColumn(name: 'oauth2_client_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OAuth2Client $client = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'string', length: 45)]
    private string $ipAddress;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'string', length: 10)]
    private string $method;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $requestParams = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $errorCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $responseTime = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
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
}
