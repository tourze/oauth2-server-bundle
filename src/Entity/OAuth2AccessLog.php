<?php

namespace Tourze\OAuth2ServerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\OAuth2ServerBundle\Repository\OAuth2AccessLogRepository;

/**
 * OAuth2访问日志实体
 *
 * 记录OAuth2端点的访问情况，包括令牌端点和授权端点的访问日志，
 * 用于安全审计、异常检测和统计分析
 */
#[ORM\Entity(repositoryClass: OAuth2AccessLogRepository::class)]
#[ORM\Table(name: 'oauth2_access_log', options: ['comment' => 'OAuth2访问日志'])]
class OAuth2AccessLog implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null; // @phpstan-ignore-line Doctrine sets this via reflection

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '端点名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $endpoint;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '客户端ID'])]
    #[Assert\Length(max: 255)]
    private ?string $clientId = null;

    #[ORM\ManyToOne(targetEntity: OAuth2Client::class)]
    #[ORM\JoinColumn(name: 'oauth2_client_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OAuth2Client $client = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户ID'])]
    #[Assert\Length(max: 255)]
    private ?string $userId = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 45, options: ['comment' => 'IP地址'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 45)]
    #[Assert\Ip]
    private string $ipAddress;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '用户代理'])]
    #[Assert\Length(max: 500)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => 'HTTP方法'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    #[Assert\Choice(choices: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'])]
    private string $method;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '请求参数'])]
    #[Assert\Type(type: 'array')]
    private ?array $requestParams = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '状态'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['success', 'error'])]
    private string $status;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '错误代码'])]
    #[Assert\Length(max: 100)]
    private ?string $errorCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误消息'])]
    #[Assert\Length(max: 65535)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '响应时间(毫秒)'])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private ?int $responseTime = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
    }

    /**
     * 创建访问日志记录
     */
    /**
     * @param array<string, mixed>|null $requestParams
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
        ?int $responseTime = null,
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

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClient(): ?OAuth2Client
    {
        return $this->client;
    }

    public function setClient(?OAuth2Client $client): void
    {
        $this->client = $client;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestParams(): ?array
    {
        return $this->requestParams;
    }

    /**
     * @param array<string, mixed>|null $requestParams
     */
    public function setRequestParams(?array $requestParams): void
    {
        $this->requestParams = $requestParams;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getResponseTime(): ?int
    {
        return $this->responseTime;
    }

    public function setResponseTime(?int $responseTime): void
    {
        $this->responseTime = $responseTime;
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    /**
     * 检查是否为成功状态
     */
    public function isSuccess(): bool
    {
        return 'success' === $this->status;
    }

    /**
     * 检查是否为错误状态
     */
    public function isError(): bool
    {
        return 'error' === $this->status;
    }

    /**
     * 获取格式化的响应时间
     */
    public function getFormattedResponseTime(): string
    {
        if (null === $this->responseTime) {
            return 'N/A';
        }

        return $this->responseTime . 'ms';
    }

    public function __toString(): string
    {
        return "{$this->endpoint} [{$this->status}] {$this->ipAddress}";
    }
}
