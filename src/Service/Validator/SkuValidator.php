<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\MissingSkuException;

class SkuValidator implements IValidator
{
    /**
     * @throws MissingSkuException
     */
    public function validate(Product $product): void
    {
        $sku = $product->getSku();

        if (null === $sku) {
            throw new MissingSkuException('The attribute [sku] is missing');
        }
    }
}
