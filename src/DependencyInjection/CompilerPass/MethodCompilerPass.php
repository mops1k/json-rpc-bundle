<?php

namespace JsonRpcBundle\DependencyInjection\CompilerPass;

use JsonRpcBundle\Handler\MethodHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MethodCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $methodHandlerDefinition = $container->findDefinition(MethodHandler::class);
        $servicesWithTags = $container->findTaggedServiceIds('jsonrpc.method');
        foreach ($servicesWithTags as $id => $tags) {
            $definition = $container->getDefinition($id);
            foreach ($tags as $tag) {
                $methodHandlerDefinition->addMethodCall('add', [
                    '$name' => $tag['methodName'],
                    '$method' => $definition,
                ]);
            }
        }
    }
}
