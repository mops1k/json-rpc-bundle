<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use JsonRpcBundle\ApiDocDescriber\MethodDescriber;
use JsonRpcBundle\ArgumentResolver\JsonRpcRequestResolver;
use JsonRpcBundle\Controller\JsonRpcController;
use JsonRpcBundle\Listener\JsonRpcExceptionListener;
use JsonRpcBundle\MethodResolver\MethodResolver;
use JsonRpcBundle\MethodResolver\MethodResolverInterface;
use Symfony\Component\HttpKernel\KernelEvents;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->set(JsonRpcRequestResolver::class)
        ->args([
            '$serializer' => service('serializer'),
            '$validator' => service('validator'),
        ])
        ->tag('controller.argument_value_resolver', ['priority' => 0]);

    $services->set(MethodResolverInterface::class)
        ->class(MethodResolver::class)
        ->args([
            '$denormalizer' => service('serializer'),
            '$validator' => service('validator'),
        ]);

    $services->set(JsonRpcController::class)
        ->args([
            '$methodHandler' => service(MethodResolverInterface::class),
            '$normalizer' => service('serializer'),
        ])
        ->tag('controller.service_arguments');

    $services->set(JsonRpcExceptionListener::class)
        ->tag('kernel.event_listener', ['event' => KernelEvents::EXCEPTION]);

    $services->set(MethodDescriber::class)
        ->args([
            '$methodResolver' => service(MethodResolverInterface::class),
            '$router' => service('router'),
            '$describer' => service('nelmio_api_doc.route_describers.route_metadata')
        ])
        ->tag('nelmio_api_doc.route_describer', ['priority' => 1000]);
};
