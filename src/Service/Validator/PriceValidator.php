<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\MissingPriceException;

class PriceValidator implements IValidator
{
    /**
     * @throws MissingPriceException
     */
    public function validate(Product $product): void
    {
        $price = $product->getPrice();

        if (null === $price) {
            throw new MissingPriceException('The attribute [price] is missing');
        }
    }
}
