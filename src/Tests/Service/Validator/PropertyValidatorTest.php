<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\Property\InvalidDataTypeException;

class PropertyValidatorTest extends AbstractValidatorTest
{
    /**
     * Wenn die SKU fehlt, wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Property
     * @group SimpleApi_Product_Validator_Property_1
     *
     */
    public function invalidDataTypeWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['properties'] = [
            'color' => 1,
        ];

        $this->expectException(InvalidDataTypeException::class);
        $this->request->init($definition);
    }
}
