<?php

namespace JsonRpcBundle\ApiDoc\Attribute;

use OpenApi\Attributes\Property;

#[\Attribute(\Attribute::TARGET_METHOD)]
readonly class Result
{
    /**
     * @param array<Property> $properties
     */
    public function __construct(public array $properties)
    {
    }
}
