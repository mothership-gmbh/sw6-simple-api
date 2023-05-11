<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\MissingTaxException;

class TaxValidator implements IValidator
{
    public function validate(Product $product): void
    {
        $tax = $product->getTax();
        if (null === $tax) {
            throw new MissingTaxException('The attribute [tax] is missing');
        }
    }
}
