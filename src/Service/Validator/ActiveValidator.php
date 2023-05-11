<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\Active\InvalidStateException;

class ActiveValidator implements IValidator
{
    /**
     * @throws InvalidStateException
     */
    public function validate(Product $product): void
    {
        $state = $product->getActive();

        if (null !== $state && !is_bool($state)) {
            throw new InvalidStateException('The provided active state is not valid. It must be a boolean value.');
        }
    }
}
