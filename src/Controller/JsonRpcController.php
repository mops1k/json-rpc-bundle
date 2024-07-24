<?php

namespace JsonRpcBundle\Controller;

use JsonRpcBundle\Exceptions\JsonRpcExceptionInterface;
use JsonRpcBundle\Exceptions\JsonRpcInternalErrorException;
use JsonRpcBundle\MethodResolver\MethodResolverInterface;
use JsonRpcBundle\Request\JsonRpcRequest;
use JsonRpcBundle\Response\JsonRpcCollectionResponse;
use JsonRpcBundle\Response\JsonRpcErrorResponse;
use JsonRpcBundle\Response\JsonRpcResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class JsonRpcController
{
    public function __construct(
        private MethodResolverInterface $methodHandler,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function __invoke(?string $namespace = null, JsonRpcRequest ...$requests): JsonRpcResponse|JsonRpcCollectionResponse|JsonRpcErrorResponse|Response
    {
        $responses = [];
        foreach ($requests as $request) {
            try {
                $result = $this->methodHandler->resolve($request, $namespace);
                if (null === $result) {
                    continue;
                }

                if (is_object($result)) {
                    $result = $this->normalizer->normalize($result, context: [
                        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                        AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
                    ]);
                }
                $responses[] = new JsonRpcResponse($result, $request->id);
            } catch (JsonRpcExceptionInterface $e) {
                $responses[] = new JsonRpcErrorResponse($e, $e->getId());
            } catch (\Throwable $e) {
                $responses[] = new JsonRpcErrorResponse(
                    new JsonRpcInternalErrorException($e->getMessage(), $request->id, $e),
                    $request->id
                );
            }
        }

        if (1 === count($responses)) {
            $response = reset($responses);
            if (false === $response) {
                return new JsonRpcResponse(id: null);
            }

            return $response;
        }

        if (0 === count($responses)) {
            return new Response(headers: ['Content-Type' => 'application/json']);
        }

        return new JsonRpcCollectionResponse($responses);
    }
}
