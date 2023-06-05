<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\Ean\InvalidDataTypeException;

class EanValidatorTest extends AbstractValidatorTest
{
    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_EanValidator
     * @group SimpleApi_Product_Validator_EanValidator_1
     *
     */
    public function invalidDataTypeWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['ean'] = 123;
        $this->expectException(InvalidDataTypeException::class);
        $this->request->init($definition);
    }
}
