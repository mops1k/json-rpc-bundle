<?php

namespace JsonRpcBundle\Request;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

readonly class JsonRpcRequest
{
    public function __construct(
        #[Groups('JsonRpcRequest')]
        #[Assert\NotBlank(allowNull: false)]
        public string $jsonrpc = '2.0',

        #[Groups('JsonRpcRequest')]
        #[Assert\NotBlank(allowNull: false)]
        public ?string $method = null,

        #[Groups('JsonRpcRequest')]
        #[Assert\NotBlank(allowNull: true)]
        public ?array $params = null,

        #[Groups('JsonRpcRequest')]
        public string|int|null $id = null,
    ) {
    }
}
