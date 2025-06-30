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
 * 切换OAuth2客户端状态控制器
 */
class ToggleStatusController extends AbstractController
{
    public function __construct(
        private readonly OAuth2ClientService $clientService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route(path: '/admin/oauth2-client/{entityId}/toggle-status', name: 'admin_oauth2_client_toggle_status', methods: ['POST'])]
    public function __invoke(AdminContext $context, Request $request): RedirectResponse
    {
        /** @var OAuth2Client $client */
        $client = $context->getEntity()->getInstance();
        
        try {
            if ($client->isEnabled()) {
                $this->clientService->disableClient($client);
                $this->addFlash('success', '客户端已禁用');
            } else {
                $this->clientService->enableClient($client);
                $this->addFlash('success', '客户端已启用');
            }
        } catch (\Throwable $e) {
            $this->addFlash('danger', '状态切换失败: ' . $e->getMessage());
        }

        return $this->redirectToIndex();
    }

    /**
     * 重定向到列表页
     */
    private function redirectToIndex(): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(OAuth2ClientCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return new RedirectResponse($url);
    }
}