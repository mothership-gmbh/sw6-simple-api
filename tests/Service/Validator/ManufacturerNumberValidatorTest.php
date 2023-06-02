<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\ManufacturerNumber\InvalidDataTypeException;

class ManufacturerNumberValidatorTest extends AbstractValidatorTest
{
    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_ManufacturerNumberValidator
     * @group SimpleApi_Product_Validator_ManufacturerNumberValidator_1
     *
     */
    public function invalidDataTypeWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['manufacturer_number'] = 123;
        $this->expectException(InvalidDataTypeException::class);
        $this->request->init($definition);
    }
}
