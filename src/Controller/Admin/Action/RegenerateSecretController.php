<?php

namespace Tourze\OAuth2ServerBundle\Controller\Admin\Action;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\OAuth2ServerBundle\Controller\Admin\OAuth2ClientCrudController;
use Tourze\OAuth2ServerBundle\Entity\OAuth2Client;
use Tourze\OAuth2ServerBundle\Service\OAuth2ClientService;

/**
 * 重新生成OAuth2客户端密钥控制器
 */
class RegenerateSecretController extends AbstractController
{
    public function __construct(
        private readonly OAuth2ClientService $clientService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route(path: '/admin/oauth2-client/{entityId}/regenerate-secret', name: 'admin_oauth2_client_regenerate_secret', methods: ['POST'])]
    public function __invoke(AdminContext $context, Request $request): RedirectResponse
    {
        /** @var OAuth2Client $client */
        $client = $context->getEntity()->getInstance();
        
        if (!$client->isConfidential()) {
            $this->addFlash('danger', '公开客户端不需要密钥');
            return $this->redirectToDetail($client);
        }

        try {
            $newSecret = $this->clientService->regenerateClientSecret($client);
            
            $this->addFlash('success', 
                "客户端密钥已重新生成。新密钥: <strong>{$newSecret}</strong><br>" .
                "<small class='text-warning'>请立即保存此密钥，系统不会再次显示明文密钥</small>"
            );
        } catch (\Throwable $e) {
            $this->addFlash('danger', '密钥重新生成失败: ' . $e->getMessage());
        }

        return $this->redirectToDetail($client);
    }

    /**
     * 重定向到详情页
     */
    private function redirectToDetail(OAuth2Client $client): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(OAuth2ClientCrudController::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($client->getId())
            ->generateUrl();

        return new RedirectResponse($url);
    }
}