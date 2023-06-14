<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\Property\InvalidDataTypeException;
use MothershipSimpleApi\Service\Validator\Exception\Trait\InvalidCodeFormatException;

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

    /**
     * Der Code muss entweder in camelCase oder snake_case sein.
     * Code in kebab-case wirft Exception.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Property
     * @group SimpleApi_Product_Validator_Property_2
     */
    public function propertyGroup_invalidCodeFormatException_KebabCase(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['properties'] = [
            'color-prop' => ['red'],
        ];

        $this->expectException(InvalidCodeFormatException::class);
        $this->request->init($definition);
    }

    /**
     * Der Code muss entweder in camelCase oder snake_case sein.
     * Code in PascalCase wirft Exception.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Property
     * @group SimpleApi_Product_Validator_Property_3
     *
     */
    public function propertyGroup_invalidCodeFormatException_PascalCase(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['properties'] = [
            'ColorProp' => ['red'],
        ];

        $this->expectException(InvalidCodeFormatException::class);
        $this->request->init($definition);
    }

    /**
     * Der Code muss entweder in camelCase oder snake_case sein.
     * Code in kebab-case wirft Exception.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Property
     * @group SimpleApi_Product_Validator_Property_4
     */
    public function propertyGroupOption_invalidCodeFormatException_KebabCase(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['properties'] = [
            'color' => ['orange-red'],
        ];

        $this->expectException(InvalidCodeFormatException::class);
        $this->request->init($definition);
    }

    /**
     * Der Code muss entweder in camelCase oder snake_case sein.
     * Code in PascalCase wirft Exception.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Property
     * @group SimpleApi_Product_Validator_Property_5
     */
    public function propertyGroupOption_invalidCodeFormatException_PascalCase(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['properties'] = [
            'color' => ['OrangeRed'],
        ];

        $this->expectException(InvalidCodeFormatException::class);
        $this->request->init($definition);
    }
}
