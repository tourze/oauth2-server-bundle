<?php

namespace Tourze\OAuth2ServerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\OAuth2ServerBundle\Repository\OAuth2ClientRepository;

/**
 * OAuth2客户端实体
 *
 * 存储第三方应用的客户端信息，包括ClientId、ClientSecret等
 * 每个客户端都关联到一个系统用户（UserInterface）
 */
#[ORM\Entity(repositoryClass: OAuth2ClientRepository::class)]
#[ORM\Table(name: 'oauth2_client', options: ['comment' => 'OAuth2客户端'])]
class OAuth2Client implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null; // @phpstan-ignore-line Doctrine sets this via reflection

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 80, unique: true, options: ['comment' => '客户端ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $clientId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '客户端密钥（加密）'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $clientSecret;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '客户端名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '客户端描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    /**
     * 关联的用户
     */
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?UserInterface $user = null;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '重定向URI列表'])]
    #[Assert\Type(type: 'array')]
    #[Assert\All(constraints: [
        new Assert\Url(),
    ])]
    private array $redirectUris = [];

    /**
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '支持的授权类型'])]
    #[Assert\Type(type: 'array')]
    #[Assert\All(constraints: [
        new Assert\Choice(choices: ['client_credentials', 'authorization_code', 'refresh_token']),
    ])]
    private array $grantTypes = ['client_credentials'];

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '客户端作用域'])]
    #[Assert\Type(type: 'array')]
    private ?array $scopes = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否为机密客户端', 'default' => true])]
    #[Assert\Type(type: 'bool')]
    private bool $confidential = true;

    #[IndexColumn]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    #[Assert\Type(type: 'bool')]
    private bool $enabled = true;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '访问令牌有效期（秒）', 'default' => 3600])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $accessTokenLifetime = 3600;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '刷新令牌有效期（秒）', 'default' => 1209600])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $refreshTokenLifetime = 1209600; // 14天

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'PKCE代码挑战方法'])]
    #[Assert\Type(type: 'array')]
    #[Assert\All(constraints: [
        new Assert\Choice(choices: ['plain', 'S256']),
    ])]
    private ?array $codeChallengeMethod = ['plain', 'S256'];

    /**
     * 关联的授权码
     * @var Collection<int, AuthorizationCode>
     */
    #[ORM\OneToMany(targetEntity: AuthorizationCode::class, mappedBy: 'client', cascade: ['remove'])]
    private Collection $authorizationCodes;

    public function __construct()
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

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array<string>
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    /**
     * @param array<string> $redirectUris
     */
    public function setRedirectUris(array $redirectUris): void
    {
        $this->redirectUris = $redirectUris;
    }

    public function addRedirectUri(string $uri): void
    {
        if (!in_array($uri, $this->redirectUris, true)) {
            $this->redirectUris[] = $uri;
        }
    }

    public function removeRedirectUri(string $uri): void
    {
        $this->redirectUris = array_filter(
            $this->redirectUris,
            fn ($existingUri) => $existingUri !== $uri
        );
    }

    public function hasRedirectUri(string $uri): bool
    {
        return in_array($uri, $this->redirectUris, true);
    }

    /**
     * @return array<string>
     */
    public function getGrantTypes(): array
    {
        return $this->grantTypes;
    }

    /**
     * @param array<string> $grantTypes
     */
    public function setGrantTypes(array $grantTypes): void
    {
        $this->grantTypes = $grantTypes;
    }

    public function supportsGrantType(string $grantType): bool
    {
        return in_array($grantType, $this->grantTypes, true);
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

    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    public function setConfidential(bool $confidential): void
    {
        $this->confidential = $confidential;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getAccessTokenLifetime(): int
    {
        return $this->accessTokenLifetime;
    }

    public function setAccessTokenLifetime(int $accessTokenLifetime): void
    {
        $this->accessTokenLifetime = $accessTokenLifetime;
    }

    public function getRefreshTokenLifetime(): int
    {
        return $this->refreshTokenLifetime;
    }

    public function setRefreshTokenLifetime(int $refreshTokenLifetime): void
    {
        $this->refreshTokenLifetime = $refreshTokenLifetime;
    }

    /**
     * @return array<string>|null
     */
    public function getCodeChallengeMethod(): ?array
    {
        return $this->codeChallengeMethod;
    }

    /**
     * @param array<string>|null $codeChallengeMethod
     */
    public function setCodeChallengeMethod(?array $codeChallengeMethod): void
    {
        $this->codeChallengeMethod = $codeChallengeMethod;
    }

    public function supportsCodeChallengeMethod(string $method): bool
    {
        return null !== $this->codeChallengeMethod && in_array($method, $this->codeChallengeMethod, true);
    }

    /**
     * @return Collection<int, AuthorizationCode>
     */
    public function getAuthorizationCodes(): Collection
    {
        return $this->authorizationCodes;
    }

    public function addAuthorizationCode(AuthorizationCode $authorizationCode): void
    {
        if (!$this->authorizationCodes->contains($authorizationCode)) {
            $this->authorizationCodes->add($authorizationCode);
            $authorizationCode->setClient($this);
        }
    }

    public function removeAuthorizationCode(AuthorizationCode $authorizationCode): void
    {
        if ($this->authorizationCodes->removeElement($authorizationCode)) {
            if ($authorizationCode->getClient() === $this) {
                $authorizationCode->setClient(null);
            }
        }
    }
}
