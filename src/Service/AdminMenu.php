<?php

namespace Tourze\OAuth2ServerBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\OAuth2ServerBundle\Entity\AuthorizationCode;
use Tourze\OAuth2ServerBundle\Entity\OAuth2AccessLog;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;

/**
 * OAuth2服务器菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('OAuth2管理')) {
            $item->addChild('OAuth2管理')
                ->setAttribute('icon', 'fas fa-shield-alt')
            ;
        }

        $oauth2Menu = $item->getChild('OAuth2管理');
        if (null === $oauth2Menu) {
            return;
        }

        // OAuth2客户端管理菜单
        $oauth2Menu->addChild('客户端管理')
            ->setUri($this->linkGenerator->getCurdListPage(OAuth2Client::class))
            ->setAttribute('icon', 'fas fa-users')
        ;

        // 授权码记录菜单
        $oauth2Menu->addChild('授权码记录')
            ->setUri($this->linkGenerator->getCurdListPage(AuthorizationCode::class))
            ->setAttribute('icon', 'fas fa-key')
        ;

        // 访问日志菜单
        $oauth2Menu->addChild('访问日志')
            ->setUri($this->linkGenerator->getCurdListPage(OAuth2AccessLog::class))
            ->setAttribute('icon', 'fas fa-list')
        ;
    }
}
