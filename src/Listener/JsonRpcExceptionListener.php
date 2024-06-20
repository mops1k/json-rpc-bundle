<?php

namespace JsonRpcBundle\Listener;

use JsonRpcBundle\Response\JsonRpcErrorResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class JsonRpcExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $request = $event->getRequest();
        $route = $request->attributes->get('_route', false);
        if (false === $route) {
            return;
        }

        if ('json_rpc_entrypoint' !== $route) {
            return;
        }
        $response = new JsonRpcErrorResponse($throwable, null, 200);

        $event->allowCustomResponseCode();
        $event->setResponse($response);
    }
}
