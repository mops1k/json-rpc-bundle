<?php

namespace JsonRpcBundle\ArgumentResolver;

use JsonRpcBundle\Exceptions\JsonRcpInvalidRequestException;
use JsonRpcBundle\Exceptions\JsonRpcParseErrorException;
use JsonRpcBundle\Request\JsonRpcRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @psalm-suppress UnusedClass
 */
readonly class JsonRpcRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private DenormalizerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!is_a($argument->getType(), JsonRpcRequest::class, true)) {
            return;
        }

        $contentTypeHeaderValue = $request->headers->get('Content-Type');
        if ('application/json' !== $contentTypeHeaderValue) {
            throw new PreconditionRequiredHttpException('Content-Type must be application/json');
        }

        try {
            // little hack to ensure what we have single rpc call or many calls in one request
            $content = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $request->getContent()));

            $resolveData = match (gettype($content)) {
                'object' => [json_decode($request->getContent(), true)],
                'array' => json_decode($request->getContent(), true),
                default => throw new JsonRpcParseErrorException('Content parse error', null)
            };

            foreach ($resolveData as $item) {
                $jsonRpcRequest = $this->serializer->denormalize(
                    data: (array) $item,
                    type: JsonRpcRequest::class,
                    context: [
                        AbstractNormalizer::GROUPS => 'JsonRpcRequest',
                        AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                    ]
                );

                $violations = $this->validator->validate($jsonRpcRequest);
                if (count($violations) > 0) {
                    throw new JsonRcpInvalidRequestException($violations);
                }

                yield $jsonRpcRequest;
            }
        } catch (\Throwable $e) {
            throw new JsonRpcParseErrorException($e->getMessage());
        }
    }
}
