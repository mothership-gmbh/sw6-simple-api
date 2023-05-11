<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\Manufacturer\InvalidDataTypeException;

class ManufacturerValidator implements IValidator
{
    /**
     * @throws InvalidDataTypeException
     */
    public function validate(Product $product): void
    {
        $manufacturer = $product->getManufacturer();

        if (null !== $manufacturer && !is_string($manufacturer)) {
            throw new InvalidDataTypeException('The provided manufacturer has no valid data type. It must be a string value.');
        }
    }
}
