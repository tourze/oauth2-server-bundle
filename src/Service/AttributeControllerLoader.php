<?php

namespace Tourze\OAuth2ServerBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\OAuth2ServerBundle\Controller\Admin\Action\RegenerateSecretController;
use Tourze\OAuth2ServerBundle\Controller\Admin\Action\ToggleStatusController;
use Tourze\OAuth2ServerBundle\Controller\AuthorizeController;
use Tourze\OAuth2ServerBundle\Controller\TokenController;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

#[AutoconfigureTag(name: 'routing.loader')]
#[Autoconfigure(public: true)]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private readonly AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->addCollection($this->controllerLoader->load(AuthorizeController::class));
        $collection->addCollection($this->controllerLoader->load(TokenController::class));
        $collection->addCollection($this->controllerLoader->load(RegenerateSecretController::class));
        $collection->addCollection($this->controllerLoader->load(ToggleStatusController::class));

        return $collection;
    }
}
