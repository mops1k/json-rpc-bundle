<?php

namespace JsonRpcBundle\ApiDoc\Describer;

use JsonRpcBundle\ApiDoc\Attribute\Result;
use JsonRpcBundle\Attribute\AsRpcMethod;
use JsonRpcBundle\Controller\JsonRpcController;
use JsonRpcBundle\MethodResolver\MethodResolverInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\OpenApi;
use OpenApi\Attributes as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\RouterInterface;

class MethodDescriber implements MethodDescriberInterface
{
    private array $openApiTypeMap = [
        'int' => 'integer',
        'bool' => 'boolean',
        'string' => 'string',
        'float' => 'float',
        'array' => 'array',
        'void' => 'null',
        'true' => 'boolean',
        'false' => 'boolean',
    ];

    public function __construct(
        protected MethodResolverInterface $methodResolver,
        protected RouterInterface $router,
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
            if (JsonRpcController::class === $route->getDefault('_controller')) {
                $rpcRoutes[] = $route;
            }
        }

        $methodsWithNamespace = [];
        $methodsInRoot = [];
        foreach ($methods as $object) {
            $reflectionClass = new \ReflectionClass($object);
            $attributes = $reflectionClass->getAttributes(AsRpcMethod::class, \ReflectionAttribute::IS_INSTANCEOF);
            $instance = null;
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                break;
            }

            if (null === $instance?->namespace) {
                $methodsInRoot[$instance?->methodName ?? ''] = $object;

                continue;
            }

            $methodsWithNamespace[$instance?->namespace ?? ''][$instance?->methodName ?? ''] = $object;
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

    /**
     * @psalm-suppress UndefinedPropertyAssignment
     * @psalm-suppress PossiblyUndefinedMethod
     */
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
        $context = Util::createContext(['nested' => $pathItem], $pathItem->_context);
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
                    default: $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue(
                    ) : Generator::UNDEFINED,
                    oneOf: \array_map(function ($type) {
                        return new OA\Schema(type: $this->openApiTypeMap[$type] ?? $type);
                    }, $types)
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
                                new OA\Schema(type: 'integer'),
                                new OA\Schema(type: 'string'),
                                new OA\Schema(type: 'null'),
                            ]
                        ),
                    ],
                    type: 'object',
                ),
            )
        );

        $resultAttributes = $reflectionMethod->getAttributes(Result::class, \ReflectionAttribute::IS_INSTANCEOF);
        $resultProperty = new OA\Property(
            property: 'result',
            type: 'null',
            enum: [null],
            example: null,
            nullable: true
        );
        $returnType = $reflectionMethod->getReturnType();
        if ($returnType instanceof \ReflectionNamedType && $returnType->isBuiltin()) {
            $resultProperty = new OA\Property(
                property: 'result',
                type: $this->openApiTypeMap[$returnType->getName()] ?? $returnType->getName(),
            );

            if ('array' === $resultProperty->type) {
                foreach ($resultAttributes as $attribute) {
                    $instance = $attribute->newInstance();
                    $resultProperty->properties = $instance->properties;
                    break;
                }
            }
        }

        if ($returnType instanceof \ReflectionUnionType) {
            $resultProperty = new OA\Property(
                property: 'result',
            );

            foreach ($returnType->getTypes() as $type) {
                $resultProperty->oneOf[] = new OA\Schema(type: $this->openApiTypeMap[$type->getName()]);
            }
        }

        if ($returnType instanceof \ReflectionNamedType && !$returnType->isBuiltin()) {
            $resultProperty = new OA\Property(
                property: 'result',
                ref: new Model(type: $returnType->getName()),
                type: 'object',
            );
        }

        $response = new OA\Response(
            response: 200,
            description: 'Response(s) for method - '.$name,

            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    oneOf: [
                        new OA\Schema(
                            description: 'Success response',
                            properties: [
                                new OA\Property(
                                    property: 'jsonrpc',
                                    type: 'string',
                                    enum: ['2.0'],
                                    example: '2.0'
                                ),
                                $resultProperty,
                                new OA\Property(
                                    property: 'id',
                                    description: 'Request ID',
                                    example: '25',
                                    oneOf: [
                                        new OA\Schema(type: 'integer'),
                                        new OA\Schema(type: 'string'),
                                        new OA\Schema(type: 'null'),
                                    ]
                                ),
                            ],
                            type: 'object',
                        ),
                        new OA\Schema(
                            description: 'Error response',
                            properties: [
                                new OA\Property(
                                    property: 'jsonrpc',
                                    type: 'string',
                                    enum: ['2.0'],
                                    example: '2.0'
                                ),
                                new OA\Property(
                                    property: 'error',
                                    properties: [
                                        new OA\Property(
                                            property: 'code',
                                            type: 'integer',
                                        ),
                                        new OA\Property(
                                            property: 'message',
                                            type: 'string',
                                        ),
                                        new OA\Property(
                                            property: 'data',
                                            oneOf: [
                                                new OA\Schema(type: 'string'),
                                                new OA\Schema(type: 'int'),
                                                new OA\Schema(type: 'float'),
                                                new OA\Schema(type: 'object'),
                                                new OA\Schema(type: 'array'),
                                            ],
                                        ),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'id',
                                    description: 'Request ID',
                                    example: '25',
                                    oneOf: [
                                        new OA\Schema(type: 'integer'),
                                        new OA\Schema(type: 'string'),
                                        new OA\Schema(type: 'null'),
                                    ]
                                ),
                            ],
                            type: 'object',
                        ),
                    ]
                )
            )
        );

        $pathItem->post = new OA\Post(
            operationId: $pathItem->path,
            requestBody: $requestBody,
            tags: null === $namespace ? ['default'] : [$namespace],
            responses: [
                $response,
            ],
        );

        $api->paths[] = $pathItem;
    }
}
