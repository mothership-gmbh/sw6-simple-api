<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\MissingTaxException;

class TaxValidatorTest extends AbstractValidatorTest
{
    /**
     * Wenn die SKU fehlt, wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Tax
     */
    public function missingSkuWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        unset($definition['tax']);

        $this->expectException(MissingTaxException::class);
        $this->request->init($definition);
    }
}
