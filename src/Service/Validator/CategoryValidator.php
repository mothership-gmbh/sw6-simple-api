<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\Category\InvalidDataTypeException;

class CategoryValidator implements IValidator
{
    public function validate(Product $product): void
    {
        $categories = $product->getCategories();

        if (null !== $categories && !is_array($categories)) {
            throw new InvalidDataTypeException('The attribute [category] is not an array');
        }
    }
}
