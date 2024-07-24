<?php

namespace JsonRpcBundle\ApiDoc\Describer;

use JsonRpcBundle\Controller\JsonRpcController;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use OpenApi\Annotations\OpenApi;
use Symfony\Component\Routing\RouterInterface;

class RemoveDefaultRpcPathDescriber implements DescriberInterface
{
    public function __construct(protected RouterInterface $router)
    {
    }

    public function describe(OpenApi $api): void
    {
        $routeCollection = $this->router->getRouteCollection();
        $routes = $routeCollection->all();
        $rpcRoutes = [];

        foreach ($routes as $route) {
            if (JsonRpcController::class === $route->getDefault('_controller')) {
                $rpcRoutes[] = $route;
            }
        }

        foreach ($api->paths as $key => $pathItem) {
            foreach ($rpcRoutes as $rpcRoute) {
                if ($pathItem->path === $rpcRoute->getPath()) {
                    unset($api->paths[$key]);
                }
            }
        }
    }
}
