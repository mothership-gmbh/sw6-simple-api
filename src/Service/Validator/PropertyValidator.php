<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\Property\InvalidDataTypeException;
use MothershipSimpleApi\Service\Validator\Exception\Trait\InvalidCodeFormatException;
use MothershipSimpleApi\Service\Validator\Trait\CodeTrait;

class PropertyValidator implements IValidator
{
    use CodeTrait;

    /**
     * @throws InvalidDataTypeException
     * @throws InvalidCodeFormatException
     */
    public function validate(Product $product): void
    {
        $properties = $product->getProperties();

        foreach ($properties as $propertyCode => $propertyOptionValues) {
            if (!is_array($propertyOptionValues)) {
                throw new InvalidDataTypeException('The argument given for the property [' . $propertyCode . '] is invalid');
            }
            $this->hasValidFormat($propertyCode);

            foreach ($propertyOptionValues as $propertyOptionValue) {
                $this->hasValidFormat($propertyOptionValue);
            }
        }
    }
}
