<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\MissingPriceException;

class PriceValidatorTest extends AbstractValidatorTest
{


    /**
     * Wenn der Preis fehlt, wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Price
     */
    public function missingPriceWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        unset($definition['price']);

        $this->expectException(MissingPriceException::class);
        $this->request->init($definition);
    }

    /**
     * Wenn der Preis keinen regulären Preis enthält, wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Price_1
     */
    public function missingRegularPriceWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        unset($definition['price']['EUR']['regular']);

        $this->expectException(MissingPriceException::class);
        $this->request->init($definition);
    }
}
