<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\Manufacturer\InvalidDataTypeException;

class ManufacturerValidatorTest extends AbstractValidatorTest
{
    /**
     * Wenn der Hersteller nicht valide ist, wird eine Exception geworfen.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Manufacturer
     * @group SimpleApi_Product_Validator_Manufacturer_1
     *
     */
    public function invalidIsoCodeSchemaWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['manufacturer'] = ['levis'];
        $this->expectException(InvalidDataTypeException::class);
        $this->request->init($definition);
    }
}
