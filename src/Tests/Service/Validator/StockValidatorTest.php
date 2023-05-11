<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\MissingStockException;

class StockValidatorTest extends AbstractValidatorTest
{
    /**
     * Wenn die SKU fehlt, wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Stock
     */
    public function missingSkuWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        unset($definition['stock']);

        $this->expectException(MissingStockException::class);
        $this->request->init($definition);
    }
}
