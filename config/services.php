<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use JsonRpcBundle\ArgumentResolver\JsonRpcRequestResolver;
use JsonRpcBundle\Controller\JsonRpcController;
use JsonRpcBundle\Handler\MethodHandler;
use JsonRpcBundle\Listener\JsonRpcExceptionListener;
use Symfony\Component\HttpKernel\KernelEvents;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->set(JsonRpcRequestResolver::class)
        ->args([
            '$serializer' => service('serializer'),
            '$validator' => service('validator'),
        ])
        ->tag('controller.argument_value_resolver', ['priority' => 0]);

    $services->set(MethodHandler::class)
        ->args([
            '$denormalizer' => service('serializer'),
            '$validator' => service('validator'),
        ]);

    $services->set(JsonRpcController::class)
        ->args([
            '$methodHandler' => service(MethodHandler::class),
            '$normalizer' => service('serializer'),
        ])
        ->tag('controller.service_arguments');

    $services->set(JsonRpcExceptionListener::class)
        ->tag('kernel.event_listener', ['event' => KernelEvents::EXCEPTION]);
};
