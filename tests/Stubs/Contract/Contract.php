<?php

namespace JsonRpcBundle\Tests\Stubs\Contract;

use Symfony\Component\Validator\Constraints as Assert;

readonly class Contract
{
    public function __construct(
        #[Assert\GreaterThanOrEqual(0)]
        public int $id,
        #[Assert\NotBlank(allowNull: false)]
        public ?string $text = null,
    ) {
    }
}
