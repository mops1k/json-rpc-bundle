<?php

namespace JsonRpcBundle;

use JsonRpcBundle\Attribute\AsRpcMethod;
use JsonRpcBundle\DependencyInjection\CompilerPass\MethodCompilerPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JsonRpcBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsRpcMethod::class,
            function (
                ChildDefinition $definition,
                AsRpcMethod $attribute,
                \ReflectionClass $reflectionClass
            ) {
                $definition->setAutowired(true);
                $definition->setAutoconfigured(true);
                $definition->addTag('jsonrpc.method', ['methodName' => $attribute->methodName]);
            }
        );
        $container->addCompilerPass(new MethodCompilerPass());
    }
}
