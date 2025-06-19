<?php

namespace Tourze\OAuth2ServerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\OAuth2ServerBundle\Repository\AuthorizationCodeRepository;

/**
 * OAuth2授权码实体
 *
 * 存储授权码模式中的临时授权码，用于换取访问令牌
 * 授权码具有短暂的有效期（通常10分钟）且只能使用一次
 */
#[ORM\Entity(repositoryClass: AuthorizationCodeRepository::class)]
#[ORM\Table(name: 'oauth2_authorization_code', options: ['comment' => 'OAuth2授权码'])]
#[ORM\Index(name: 'idx_oauth2_auth_code', columns: ['code'])]
#[ORM\Index(name: 'idx_oauth2_auth_expires', columns: ['expires_at'])]
class AuthorizationCode implements \Stringable
{
    use CreateTimeAware;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 128, unique: true, options: ['comment' => '授权码'])]
    private string $code;

    /**
     * 关联的OAuth2客户端
     */
    #[ORM\ManyToOne(targetEntity: OAuth2Client::class, inversedBy: 'authorizationCodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?OAuth2Client $client = null;

    /**
     * 授权的用户
     */
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserInterface $user;

    #[ORM\Column(type: Types::STRING, length: 2000, options: ['comment' => '重定向URI'])]
    private string $redirectUri;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '过期时间'])]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '授权作用域'])]
    private ?array $scopes = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => 'PKCE代码挑战'])]
    private ?string $codeChallenge = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => 'PKCE代码挑战方法'])]
    private ?string $codeChallengeMethod = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否已使用', 'default' => false])]
    private bool $used = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '状态参数'])]
    private ?string $state = null;


    public function __toString(): string
    {
        return $this->code ?? '';
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getClient(): ?OAuth2Client
    {
        return $this->client;
    }

    public function setClient(?OAuth2Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): static
    {
        $this->redirectUri = $redirectUri;
        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getScopes(): ?array
    {
        return $this->scopes;
    }

    public function setScopes(?array $scopes): static
    {
        $this->scopes = $scopes;
        return $this;
    }

    public function getCodeChallenge(): ?string
    {
        return $this->codeChallenge;
    }

    public function setCodeChallenge(?string $codeChallenge): static
    {
        $this->codeChallenge = $codeChallenge;
        return $this;
    }

    public function getCodeChallengeMethod(): ?string
    {
        return $this->codeChallengeMethod;
    }

    public function setCodeChallengeMethod(?string $codeChallengeMethod): static
    {
        $this->codeChallengeMethod = $codeChallengeMethod;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): static
    {
        $this->used = $used;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;
        return $this;
    }


    /**
     * 检查授权码是否过期
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    /**
     * 检查授权码是否有效（未过期且未使用）
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->used;
    }

    /**
     * 验证PKCE代码验证器
     */
    public function verifyCodeVerifier(string $codeVerifier): bool
    {
        if (!$this->codeChallenge) {
            // 如果没有代码挑战，则不需要验证
            return true;
        }

        switch ($this->codeChallengeMethod) {
            case 'plain':
                return hash_equals($this->codeChallenge, $codeVerifier);
            
            case 'S256':
                $hash = hash('sha256', $codeVerifier, true);
                $challenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
                return hash_equals($this->codeChallenge, $challenge);
            
            default:
                return false;
        }
    }

    /**
     * 创建新的授权码
     */
    public static function create(
        OAuth2Client $client,
        UserInterface $user,
        string $redirectUri,
        ?array $scopes = null,
        int $expiresInMinutes = 10,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null,
        ?string $state = null
    ): self {
        $authCode = new self();
        $authCode->setCode(bin2hex(random_bytes(32)));
        $authCode->setClient($client);
        $authCode->setUser($user);
        $authCode->setRedirectUri($redirectUri);
        $authCode->setScopes($scopes);
        $authCode->setExpiresAt(new \DateTimeImmutable("+{$expiresInMinutes} minutes"));
        $authCode->setCodeChallenge($codeChallenge);
        $authCode->setCodeChallengeMethod($codeChallengeMethod);
        $authCode->setState($state);

        return $authCode;
    }
}
