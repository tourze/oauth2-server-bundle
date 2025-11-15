<?php

namespace Tourze\OAuth2ServerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
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
class AuthorizationCode implements \Stringable
{
    use CreateTimeAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 128, unique: true, options: ['comment' => '授权码'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
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
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?UserInterface $user = null;

    #[ORM\Column(type: Types::STRING, length: 2000, options: ['comment' => '重定向URI'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    #[Assert\Url]
    private string $redirectUri;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '过期时间'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $expireTime;

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '授权作用域'])]
    #[Assert\Type(type: 'array')]
    private ?array $scopes = null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => 'PKCE代码挑战'])]
    #[Assert\Length(max: 128)]
    private ?string $codeChallenge = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => 'PKCE代码挑战方法'])]
    #[Assert\Choice(choices: ['plain', 'S256'])]
    #[Assert\Length(max: 10)]
    private ?string $codeChallengeMethod = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否已使用', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $used = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '状态参数'])]
    #[Assert\Length(max: 255)]
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

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getClient(): ?OAuth2Client
    {
        return $this->client;
    }

    public function setClient(?OAuth2Client $client): void
    {
        $this->client = $client;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getExpireTime(): \DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(\DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @return array<string>|null
     */
    public function getScopes(): ?array
    {
        return $this->scopes;
    }

    /**
     * @param array<string>|null $scopes
     */
    public function setScopes(?array $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function getCodeChallenge(): ?string
    {
        return $this->codeChallenge;
    }

    public function setCodeChallenge(?string $codeChallenge): void
    {
        $this->codeChallenge = $codeChallenge;
    }

    public function getCodeChallengeMethod(): ?string
    {
        return $this->codeChallengeMethod;
    }

    public function setCodeChallengeMethod(?string $codeChallengeMethod): void
    {
        $this->codeChallengeMethod = $codeChallengeMethod;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): void
    {
        $this->used = $used;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * 检查授权码是否过期
     */
    public function isExpired(): bool
    {
        return $this->expireTime < new \DateTime();
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
        if (null === $this->codeChallenge || '' === $this->codeChallenge) {
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
    /**
     * @param array<string>|null $scopes
     */
    public static function create(
        OAuth2Client $client,
        ?UserInterface $user,
        string $redirectUri,
        ?array $scopes = null,
        int $expiresInMinutes = 10,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null,
        ?string $state = null,
    ): self {
        $authCode = new self();
        $authCode->setCode(bin2hex(random_bytes(32)));
        $authCode->setClient($client);
        $authCode->setUser($user);
        $authCode->setRedirectUri($redirectUri);
        $authCode->setScopes($scopes);
        $authCode->setExpireTime(new \DateTimeImmutable("+{$expiresInMinutes} minutes"));
        $authCode->setCodeChallenge($codeChallenge);
        $authCode->setCodeChallengeMethod($codeChallengeMethod);
        $authCode->setState($state);

        return $authCode;
    }
}
