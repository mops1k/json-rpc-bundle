<?php

// config/services.php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $container): void {
    // default configuration for services in *this* file
    $services = $container->services()
        ->defaults()
        ->autowire()      // Automatically injects dependencies in your services.
        ->autoconfigure() // Automatically registers your services as commands, event subscribers, etc.
    ;

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('JsonRpcBundle\\Tests\\Stubs\\', __DIR__.'/')
        ->exclude([__DIR__.'/services.php', __DIR__.'/TestKernel.php']);
};
