<?php

namespace JsonRpcBundle\Exceptions;

enum FaultCode: int
{
    case PARSE_ERROR = -32700;
    case INVALID_REQUEST = -32600;
    case METHOD_NOT_FOUND = -32601;
    case INVALID_PARAMS = -32602;
    case INTERNAL_ERROR = -32603;

    private const array ERROR_MESSAGES = [
        self::PARSE_ERROR->value => 'Invalid JSON was received by the server.',
        self::INVALID_REQUEST->value => 'The JSON sent is not a valid Request object.',
        self::METHOD_NOT_FOUND->value => 'The requested method was not found.',
        self::INVALID_PARAMS->value => 'Invalid method parameters.',
        self::INTERNAL_ERROR->value => 'Internal JSON-RPC error.',
    ];

    public function getMessage(): string
    {
        return self::ERROR_MESSAGES[$this->value];
    }
}
