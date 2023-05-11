<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\MissingStockException;

class StockValidator implements IValidator
{
    public function validate(Product $product): void
    {
        $stock = $product->getStock();

        if (null === $stock) {
            throw new MissingStockException('The attribute [stock] is missing');
        }
    }
}
