<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\ManufacturerNumber\InvalidDataTypeException;

class ManufacturerNumberValidator implements IValidator
{
    /**
     * @throws InvalidDataTypeException
     */
    public function validate(Product $product): void
    {
        $manufacturerNumber = $product->getManufacturerNumber();

        if (null !== $manufacturerNumber && !is_string($manufacturerNumber)) {
            throw new InvalidDataTypeException('The provided ManufacturerNumber has no valid data type. It must be a string value.');
        }
    }
}
