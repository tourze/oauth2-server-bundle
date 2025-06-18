<?php

namespace Tourze\OAuth2ServerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\OAuth2ServerBundle\Repository\OAuth2ClientRepository;

/**
 * OAuth2客户端实体
 * 
 * 存储第三方应用的客户端信息，包括ClientId、ClientSecret等
 * 每个客户端都关联到一个系统用户（UserInterface）
 * 
 * @phpstan-ignore-next-line
 */
#[ORM\Entity(repositoryClass: OAuth2ClientRepository::class)]
#[ORM\Table(name: 'oauth2_client', options: ['comment' => 'OAuth2客户端'])]
#[ORM\Index(name: 'idx_oauth2_client_id', columns: ['client_id'])]
#[ORM\Index(name: 'idx_oauth2_client_user', columns: ['user_id'])]
class OAuth2Client implements \Stringable
{
    use TimestampableAware;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    /**
     * 客户端ID，用于公开标识客户端应用
     */
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 80, unique: true, options: ['comment' => '客户端ID'])]
    private string $clientId;

    /**
     * 客户端密钥，用于客户端认证（已加密）
     */
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '客户端密钥（加密）'])]
    private string $clientSecret;

    /**
     * 客户端名称
     */
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '客户端名称'])]
    private string $name;

    /**
     * 客户端描述
     */
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '客户端描述'])]
    private ?string $description = null;

    /**
     * 关联的用户
     */
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserInterface $user;

    /**
     * 重定向URI列表（JSON格式存储）
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '重定向URI列表'])]
    private array $redirectUris = [];

    /**
     * 支持的授权类型
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '支持的授权类型'])]
    private array $grantTypes = ['client_credentials'];

    /**
     * 客户端作用域
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '客户端作用域'])]
    private ?array $scopes = null;

    /**
     * 是否为机密客户端（需要认证）
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否为机密客户端', 'default' => true])]
    private bool $confidential = true;

    /**
     * 客户端是否启用
     */
    #[IndexColumn]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    private bool $enabled = true;

    /**
     * 访问令牌有效期（秒）
     */
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '访问令牌有效期（秒）', 'default' => 3600])]
    private int $accessTokenLifetime = 3600;

    /**
     * 刷新令牌有效期（秒）
     */
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '刷新令牌有效期（秒）', 'default' => 1209600])]
    private int $refreshTokenLifetime = 1209600; // 14天

    /**
     * PKCE支持的代码挑战方法
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'PKCE代码挑战方法'])]
    private ?array $codeChallengeMethod = ['plain', 'S256'];

    /**
     * 关联的授权码
     */
    #[ORM\OneToMany(targetEntity: AuthorizationCode::class, mappedBy: 'client', cascade: ['remove'])]
    private Collection $authorizationCodes;

    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]#[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]public function __construct()
    {
        $this->authorizationCodes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? $this->clientId ?? '';
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): static
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): static
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    public function setRedirectUris(array $redirectUris): static
    {
        $this->redirectUris = $redirectUris;
        return $this;
    }

    public function addRedirectUri(string $uri): static
    {
        if (!in_array($uri, $this->redirectUris, true)) {
            $this->redirectUris[] = $uri;
        }
        return $this;
    }

    public function removeRedirectUri(string $uri): static
    {
        $this->redirectUris = array_filter(
            $this->redirectUris,
            fn($existingUri) => $existingUri !== $uri
        );
        return $this;
    }

    public function hasRedirectUri(string $uri): bool
    {
        return in_array($uri, $this->redirectUris, true);
    }

    public function getGrantTypes(): array
    {
        return $this->grantTypes;
    }

    public function setGrantTypes(array $grantTypes): static
    {
        $this->grantTypes = $grantTypes;
        return $this;
    }

    public function supportsGrantType(string $grantType): bool
    {
        return in_array($grantType, $this->grantTypes, true);
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

    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    public function setConfidential(bool $confidential): static
    {
        $this->confidential = $confidential;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getAccessTokenLifetime(): int
    {
        return $this->accessTokenLifetime;
    }

    public function setAccessTokenLifetime(int $accessTokenLifetime): static
    {
        $this->accessTokenLifetime = $accessTokenLifetime;
        return $this;
    }

    public function getRefreshTokenLifetime(): int
    {
        return $this->refreshTokenLifetime;
    }

    public function setRefreshTokenLifetime(int $refreshTokenLifetime): static
    {
        $this->refreshTokenLifetime = $refreshTokenLifetime;
        return $this;
    }

    public function getCodeChallengeMethod(): ?array
    {
        return $this->codeChallengeMethod;
    }

    public function setCodeChallengeMethod(?array $codeChallengeMethod): static
    {
        $this->codeChallengeMethod = $codeChallengeMethod;
        return $this;
    }

    public function supportsCodeChallengeMethod(string $method): bool
    {
        return $this->codeChallengeMethod && in_array($method, $this->codeChallengeMethod, true);
    }

    /**
     * @return Collection<int, AuthorizationCode>
     */
    public function getAuthorizationCodes(): Collection
    {
        return $this->authorizationCodes;
    }

    public function addAuthorizationCode(AuthorizationCode $authorizationCode): static
    {
        if (!$this->authorizationCodes->contains($authorizationCode)) {
            $this->authorizationCodes->add($authorizationCode);
            $authorizationCode->setClient($this);
        }
        return $this;
    }

    public function removeAuthorizationCode(AuthorizationCode $authorizationCode): static
    {
        if ($this->authorizationCodes->removeElement($authorizationCode)) {
            if ($authorizationCode->getClient() === $this) {
                $authorizationCode->setClient(null);
            }
        }
        return $this;
    }}
