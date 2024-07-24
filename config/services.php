<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use JsonRpcBundle\ApiDoc\Describer\MethodDescriber;
use JsonRpcBundle\ApiDoc\Describer\MethodDescriberInterface;
use JsonRpcBundle\ApiDoc\Describer\RemoveDefaultRpcPathDescriber;
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

    $services->set(MethodDescriberInterface::class)
        ->class(MethodDescriber::class)
        ->args([
            '$methodResolver' => service(MethodResolverInterface::class),
            '$router' => service('router'),
        ])
        ->tag('nelmio_api_doc.route_describer', ['priority' => -10000]);

    $services->set(RemoveDefaultRpcPathDescriber::class)
        ->args([
            '$router' => service('router'),
        ])
        ->tag('nelmio_api_doc.route_describer', ['priority' => 100000]);
};
