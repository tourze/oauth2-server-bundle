<?php

namespace Tourze\OAuth2ServerBundle\Service;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Repository\OAuth2ClientRepository;

/**
 * OAuth2客户端管理服务
 * 
 * 负责客户端的创建、验证、管理等功能
 */
class OAuth2ClientService
{
    public function __construct(
        private readonly OAuth2ClientRepository $clientRepository,
    ) {
    }

    /**
     * 创建新的OAuth2客户端
     */
    public function createClient(
        UserInterface $user,
        string $name,
        array $redirectUris = [],
        array $grantTypes = ['client_credentials'],
        ?string $description = null,
        bool $confidential = true,
        ?array $scopes = null
    ): OAuth2Client {
        $client = new OAuth2Client();
        $client->setUser($user);
        $client->setName($name);
        $client->setDescription($description);
        $client->setRedirectUris($redirectUris);
        $client->setGrantTypes($grantTypes);
        $client->setConfidential($confidential);
        $client->setScopes($scopes);

        // 生成客户端ID
        $clientId = $this->generateClientId();
        $client->setClientId($clientId);

        // 生成并加密客户端密钥
        $plainSecret = $this->generateClientSecret();
        $hashedSecret = $this->hashClientSecret($plainSecret);
        $client->setClientSecret($hashedSecret);

        $this->clientRepository->save($client);

        // 返回明文密钥供客户端保存（仅此一次）
        $client->setClientSecret($plainSecret);
        return $client;
    }

    /**
     * 验证客户端凭证
     */
    public function validateClient(string $clientId, ?string $clientSecret = null): ?OAuth2Client
    {
        $client = $this->clientRepository->findByClientId($clientId);
        if (!$client) {
            return null;
        }

        // 机密客户端需要验证密钥
        if ($client->isConfidential()) {
            if (!$clientSecret || !$this->verifyClientSecret($client, $clientSecret)) {
                return null;
            }
        }

        return $client;
    }

    /**
     * 验证客户端密钥
     */
    public function verifyClientSecret(OAuth2Client $client, string $plainSecret): bool
    {
        return password_verify($plainSecret, $client->getClientSecret());
    }

    /**
     * 验证重定向URI
     */
    public function validateRedirectUri(OAuth2Client $client, string $redirectUri): bool
    {
        $allowedUris = $client->getRedirectUris();

        if (empty($allowedUris)) {
            return false;
        }

        // 精确匹配
        if (in_array($redirectUri, $allowedUris, true)) {
            return true;
        }

        // 允许子路径匹配（可选，根据安全策略决定）
        foreach ($allowedUris as $allowedUri) {
            if (str_starts_with($redirectUri, $allowedUri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查客户端是否支持指定的授权类型
     */
    public function supportsGrantType(OAuth2Client $client, string $grantType): bool
    {
        return $client->supportsGrantType($grantType);
    }

    /**
     * 更新客户端信息
     */
    public function updateClient(OAuth2Client $client): void
    {
        $this->clientRepository->save($client);
    }

    /**
     * 重新生成客户端密钥
     */
    public function regenerateClientSecret(OAuth2Client $client): string
    {
        $plainSecret = $this->generateClientSecret();
        $hashedSecret = $this->hashClientSecret($plainSecret);
        
        $client->setClientSecret($hashedSecret);
        $this->clientRepository->save($client);
        
        return $plainSecret;
    }

    /**
     * 禁用客户端
     */
    public function disableClient(OAuth2Client $client): void
    {
        $client->setEnabled(false);
        $this->clientRepository->save($client);
    }

    /**
     * 启用客户端
     */
    public function enableClient(OAuth2Client $client): void
    {
        $client->setEnabled(true);
        $this->clientRepository->save($client);
    }

    /**
     * 删除客户端
     */
    public function deleteClient(OAuth2Client $client): void
    {
        $this->clientRepository->remove($client);
    }

    /**
     * 获取用户的客户端列表
     */
    public function getClientsByUser(UserInterface $user): array
    {
        return $this->clientRepository->findByUser($user);
    }

    /**
     * 生成客户端ID
     */
    private function generateClientId(): string
    {
        do {
            $clientId = 'client_' . bin2hex(random_bytes(16));
        } while ($this->clientRepository->findByClientId($clientId));
        
        return $clientId;
    }

    /**
     * 生成客户端密钥
     */
    private function generateClientSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * 加密客户端密钥
     */
    private function hashClientSecret(string $plainSecret): string
    {
        return password_hash($plainSecret, PASSWORD_BCRYPT);
    }
}
