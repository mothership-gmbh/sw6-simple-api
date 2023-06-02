<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\Ean\InvalidDataTypeException;

class EanValidator implements IValidator
{
    /**
     * @throws InvalidDataTypeException
     */
    public function validate(Product $product): void
    {
        $ean = $product->getEan();

        if (null !== $ean && !is_string($ean)) {
            throw new InvalidDataTypeException('The provided EAN has no valid data type. It must be a string value.');
        }
    }
}
