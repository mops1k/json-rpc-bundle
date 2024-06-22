<?php

namespace JsonRpcBundle\MethodResolver;

use JsonRpcBundle\Attribute\RpcMethodContract;
use JsonRpcBundle\Exceptions\JsonRpcInternalErrorException;
use JsonRpcBundle\Exceptions\JsonRpcInvalidParamsException;
use JsonRpcBundle\Exceptions\JsonRpcMethodNotExistsException;
use JsonRpcBundle\Request\JsonRpcRequest;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @psalm-suppress UnusedClass
 */
class MethodResolver implements MethodResolverInterface
{
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    private array $methods = [];

    /**
     * @psalm-suppress ArgumentTypeCoercion
     *
     * @throws \ReflectionException
     * @throws ExceptionInterface
     */
    public function resolve(JsonRpcRequest $jsonRpcRequest): mixed
    {
        if (null === $jsonRpcRequest->method) {
            throw new JsonRpcMethodNotExistsException(
                'Method is not defined.',
                $jsonRpcRequest->id
            );
        }

        if (!\array_key_exists($jsonRpcRequest->method, $this->methods)) {
            throw new JsonRpcMethodNotExistsException(
                \sprintf('Method "%s" does not exists', $jsonRpcRequest->method),
                $jsonRpcRequest->id
            );
        }

        $contractClassName = null;
        $method = $this->methods[$jsonRpcRequest->method];

        $methodReflectionClass = new \ReflectionClass($method);
        $attributes = $methodReflectionClass->getAttributes(RpcMethodContract::class);
        foreach ($attributes as $attribute) {
            if (RpcMethodContract::class !== $attribute->getName()) {
                continue;
            }

            $instance = $attribute->newInstance();
            $contractClassName = $instance->className;
        }

        $index = 0;
        if (null === $contractClassName) {
            $parameters = $methodReflectionClass->getMethod('__invoke')->getParameters();
            $params = [];
            if (null !== $jsonRpcRequest->params) {
                foreach ($parameters as $parameter) {
                    if (\array_key_exists($parameter->getName(), $jsonRpcRequest->params)) {
                        $params[$parameter->getName()] = $jsonRpcRequest->params[$parameter->getName(
                        )] ?? $parameter->getDefaultValue();
                        ++$index;

                        continue;
                    }

                    $params[$parameter->getName()] = $jsonRpcRequest->params[$index] ?? $parameter->getDefaultValue();
                }

                if ($method instanceof ValidateMethodParametersInterface) {
                    $violations = $this->validator->validate(
                        $params,
                        $method->configureValidation(),
                        $method->validationGroups()
                    );
                    if (\count($violations) > 0) {
                        throw new JsonRpcInvalidParamsException($violations, $jsonRpcRequest->id);
                    }
                }
            }

            $result = null === $jsonRpcRequest->params ? $method() : $method(...$params);

            $methodReflection = $methodReflectionClass->getMethod('__invoke');
            $returnType = $methodReflection->getReturnType();
            if (null === $returnType) {
                return null;
            }
            if ($returnType instanceof \ReflectionNamedType) {
                $returnTypeName = $returnType->getName();
                if (\array_key_exists($returnTypeName, [
                    'void' => true,
                    'never' => true,
                    'null' => true,
                ])) {
                    return null;
                }
            }

            return $result;
        }

        $params = [];

        $contractReflectionClass = new \ReflectionClass($contractClassName);
        foreach ($contractReflectionClass->getProperties() as $property) {
            if (\array_key_exists($property->getName(), $jsonRpcRequest->params ?? [])) {
                $params[$property->getName()] = $jsonRpcRequest->params[$property->getName()] ?? null;
                ++$index;

                continue;
            }

            $params[$property->getName()] = $jsonRpcRequest->params[$index] ?? null;
            ++$index;
        }

        $contract = $this->denormalizer->denormalize(
            $params,
            $contractClassName,
            context: [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );

        $violations = $this->validator->validate($contract);
        if (count($violations) > 0) {
            throw new JsonRpcInvalidParamsException($violations, $jsonRpcRequest->id);
        }

        return $method($contract);
    }

    public function add(string $name, object $method): void
    {
        if (\array_key_exists($name, $this->methods)) {
            throw new JsonRpcInternalErrorException(
                \sprintf('Method with name "%s" already defined.', $name)
            );
        }

        $this->methods[$name] = $method;
    }
}
