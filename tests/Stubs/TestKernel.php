<?php

namespace JsonRpcBundle\Tests\Stubs;

use JsonRpcBundle\JsonRpcBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader as ContainerPhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\ContainerLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    #[\Override]
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new JsonRpcBundle(),
            new NelmioApiDocBundle(),
        ];
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $container->import(__DIR__.'/services.php');
    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->setParameter('kernel.secret', 'null');
            $container->register('kernel', self::class)
                ->addTag('controller.service_arguments')
                ->setAutoconfigured(true)
                ->setSynthetic(true)
                ->setPublic(true);

            $kernelDefinition = $container->getDefinition('kernel');
            $kernelDefinition->addTag('routing.route_loader');

            $container->addObjectResource($this);

            $container->setParameter('kernel.environment', 'test');
            $container->prependExtensionConfig('framework', [
                'test' => true,
                'serializer' => [
                    'enabled' => true,
                ],
                'property_access' => true,
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
            ]);

            $configureContainer = new \ReflectionMethod($this, 'configureContainer');
            $configuratorClass = $configureContainer->getNumberOfParameters() > 0 && ($type = $configureContainer->getParameters()[0]->getType()) instanceof \ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;

            if ($configuratorClass && !is_a(ContainerConfigurator::class, $configuratorClass, true)) {
                $configureContainer->getClosure($this)($container, $loader);

                return;
            }

            $file = (new \ReflectionObject($this))->getFileName();
            /* @var ContainerPhpFileLoader $kernelLoader */
            $kernelLoader = $loader->getResolver()->resolve($file);
            $kernelLoader->setCurrentDir(\dirname($file));
            $closure = \Closure::bind(fn &() => $this->instanceof, $kernelLoader, $kernelLoader)();
            $instanceof = &$closure;

            $valuePreProcessor = AbstractConfigurator::$valuePreProcessor;
            AbstractConfigurator::$valuePreProcessor = fn ($value) => $this === $value ? new Reference('kernel') : $value;

            try {
                $configureContainer->getClosure($this)(new ContainerConfigurator($container, $kernelLoader, $instanceof, $file, $file, $this->getEnvironment()), $loader, $container);
            } finally {
                $instanceof = [];
                $kernelLoader->registerAliasesForSinglyImplementedInterfaces();
                AbstractConfigurator::$valuePreProcessor = $valuePreProcessor;
            }

            $container->setAlias(self::class, 'kernel')->setPublic(true);

            return $container;
        });
    }

    public function loadRoutes(ContainerLoader $loader): RouteCollection
    {
        $file = (new \ReflectionObject($this))->getFileName();
        $collection = new RouteCollection();
        if (false === $file) {
            return $collection;
        }
        /* @var PhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        if (!$kernelLoader instanceof PhpFileLoader) {
            return $collection;
        }
        $configurator = new RoutingConfigurator($collection, $kernelLoader, $file, $file, 'test');
        $configurator->import(__DIR__.'/../../src/Resources/config/routing/json-rpc-bundle.php');
        $configurator
            ->add('nelmio_api_doc', '/api/doc.json')
            ->methods([Request::METHOD_GET])
            ->controller('nelmio_api_doc.controller.swagger');

        return $collection;
    }
}
