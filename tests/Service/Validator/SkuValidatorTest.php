<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\MissingSkuException;

class SkuValidatorTest extends AbstractValidatorTest
{
    /**
     * Wenn die SKU fehlt, wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Sku
     */
    public function missingSkuWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        unset($definition['sku']);

        $this->expectException(MissingSkuException::class);
        $this->request->init($definition);
    }
}
