<?php

namespace JsonRpcBundle\ApiDocDescriber;

use JsonRpcBundle\Attribute\AsRpcMethod;
use JsonRpcBundle\Controller\JsonRpcController;
use JsonRpcBundle\MethodResolver\MethodResolverInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations\OpenApi;
use OpenApi\Attributes as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\RouterInterface;

class MethodDescriber implements DescriberInterface
{
    use RouteDescriberTrait;

    private array $openApiTypeMap = [
        'int' => 'integer',
        'bool' => 'boolean',
        'string' => 'string',
        'float' => 'float',
        'array' => 'array',
    ];

    public function __construct(
        protected MethodResolverInterface $methodResolver,
        protected RouterInterface $router,
        protected RouteDescriberInterface $describer,
    ) {
    }

    public function describe(OpenApi $api): void
    {
        $methods = $this->methodResolver->getMethodsList();
        $api->paths = [];

        $routeCollection = $this->router->getRouteCollection();
        $routes = $routeCollection->all();
        $rpcRoutes = [];
        foreach ($routes as $route) {
            if ($route->getDefault('_controller') === JsonRpcController::class) {
                $rpcRoutes[] = $route;
            }
        }

        $methodsWithNamespace = [];
        $methodsInRoot = [];
        foreach ($methods as $object) {
            $reflectionClass = new \ReflectionClass($object);
            $attributes = $reflectionClass->getAttributes(AsRpcMethod::class, \ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                /** @var AsRpcMethod $instance */
                $instance = $attribute->newInstance();
                break;
            }

            if ($instance->namespace === null) {
                $methodsInRoot[$instance->methodName] = $object;

                continue;
            }

            $methodsWithNamespace[$instance->namespace][$instance->methodName] = $object;
        }

        foreach ($rpcRoutes as $route) {
            $path = $route->getPath();
            if (str_contains($path, '{namespace}')) {
                foreach ($methodsWithNamespace as $namespace => $item) {
                    foreach ($item as $name => $object) {
                        $pathItem = new OA\PathItem(
                            path: \str_replace('{namespace}', $namespace, $route->getPath()).'#'.$name,
                        );
                        $this->describeMethod($api, $pathItem, $name, $object, $namespace);
                    }
                }

                continue;
            }

            foreach ($methodsInRoot as $name => $object) {
                $pathItem = new OA\PathItem(
                    path: $route->getPath().'#'.$name,
                );
                $this->describeMethod($api, $pathItem, $name, $object);
            }
        }

        Generator::$context = null;
    }

    private function describeMethod(
        OpenApi $api,
        OA\PathItem $pathItem,
        string $name,
        object $object,
        ?string $namespace = null
    ): void {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod('__invoke');
        $declaringClass = $reflectionMethod->getDeclaringClass();
        $context = Util::createContext(['nested' => $pathItem, $pathItem->_context]);
        $context->namespace = $declaringClass->getNamespaceName();
        $context->class = $declaringClass->getShortName();
        $context->method = $reflectionMethod->name;
        $context->filename = $reflectionMethod->getFileName();
        Generator::$context = $context;

        $paramsDescriber = null;
        $properties = [];
        $attributes = $reflectionMethod->getAttributes(OA\Property::class, \ReflectionAttribute::IS_INSTANCEOF);
        foreach ($attributes as $attribute) {
            $properties[] = $attribute->newInstance();
        }
        if (0 === count($properties)) {
            foreach ($reflectionMethod->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $paramsDescriber = new OA\Property(
                        property: 'params',
                        ref: new Model(type: $type->getName()),
                        type: 'object',
                    );

                    break;
                }

                $types = [];
                if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                    $types[] = $type->getName();
                    if ($type->allowsNull()) {
                        $types[] = 'null';
                    }
                }

                if ($type instanceof \ReflectionUnionType) {
                    foreach ($type->getTypes() as $typeItem) {
                        $types[] = $typeItem->getName();
                    }
                    if ($type->allowsNull()) {
                        $types[] = 'null';
                    }
                }

                $properties[] = new OA\Property(
                    property: $parameter->getName(),
                    default: $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : Generator::UNDEFINED,
                    oneOf: \array_map(function ($type) { return new OA\Schema(type: $this->openApiTypeMap[$type] ?? $type); }, $types)
                );
            }
        }

        if (0 < count($properties)) {
            $paramsDescriber = new OA\Property(
                property: 'params',
                properties: $properties,
                type: 'object',
            );
        }

        if (null === $paramsDescriber) {
            $paramsDescriber = new OA\Property(
                property: 'params',
                type: 'null',
                default: null,
            );
        }

        $requestBody = new OA\RequestBody(
            request: $name,
            content: new OA\MediaType(
                'application/json',
                schema: new OA\Schema(
                    required: ['jsonrpc', 'id', 'method'],
                    properties: [
                        new OA\Property(
                            property: 'jsonrpc',
                            type: 'string',
                            enum: ['2.0'],
                            example: '2.0'
                        ),
                        new OA\Property(
                            property: 'method',
                            type: 'string',
                            enum: [$name],
                            example: $name,
                        ),
                        $paramsDescriber,
                        new OA\Property(
                            property: 'id',
                            description: 'Request ID',
                            example: 25,
                            oneOf: [
                                new OA\Schema(type: 'int'),
                                new OA\Schema(type: 'string'),
                                new OA\Schema(type: 'null'),
                            ]
                        )
                    ],
                    type: 'object',
                ),
            )
        );

        $pathItem->post = new OA\Post(
            operationId: $pathItem->path,
            requestBody: $requestBody,
            tags: $namespace === null ? ['default'] : [$namespace],
        );

        $api->paths[] = $pathItem;
    }
}
