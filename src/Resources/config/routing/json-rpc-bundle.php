<?php

use JsonRpcBundle\Controller\JsonRpcController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('json_rpc_entrypoint', '/rpc')
        ->methods([Request::METHOD_POST])
        ->controller(JsonRpcController::class);
    $routes->add('json_rpc_namespace_entrypoint', '/rpc/{namespace}')
        ->methods([Request::METHOD_POST])
        ->controller(JsonRpcController::class);
};
