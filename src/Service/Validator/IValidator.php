<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;

interface IValidator
{
    public function validate(Product $product): void;
}
