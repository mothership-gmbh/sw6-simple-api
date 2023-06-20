<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingTypeDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingValuesException;
use MothershipSimpleApi\Service\Validator\Exception\Trait\InvalidCodeFormatException;

class CustomFieldValidatorTest extends AbstractValidatorTest
{
    /**
     * Wenn der Iso-Code nicht die Struktur de-DE hat, so wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_CustomField
     * @group SimpleApi_Product_Validator_CustomField_1
     *
     */
    public function invalidIsoCodeSchemaWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'boolean',
                'values' => [
                    'de_DE' => true,
                ],
            ],
        ];
        $this->expectException(InvalidIsoCodeException::class);
        $this->request->init($definition);
    }

    /**
     * Wenn das Argument kein Array ist, gibt es eine Exception
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_CustomField
     * @group SimpleApi_Product_Validator_CustomField_2
     *
     */
    public function invalidDataTypeException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['custom_fields'] = [
            'ms_boolean' => 1,
        ];
        $this->expectException(InvalidDefinitionException::class);
        $this->request->init($definition);
    }

    /**
     * Wenn der Type fehlt, gibt es eine Exception
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_CustomField
     * @group SimpleApi_Product_Validator_CustomField_3
     *
     */
    public function missingTypeException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['custom_fields'] = [
            'ms_boolean' => [

            ],
        ];
        $this->expectException(MissingTypeDefinitionException::class);
        $this->request->init($definition);
    }

    /**
     * Wenn der Value fehlt, gibt es eine Exception
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_CustomField
     * @group SimpleApi_Product_Validator_CustomField_4
     *
     */
    public function missingValueException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['custom_fields'] = [
            'ms_boolean' => [
                'type' => 'boolean',
            ],
        ];
        $this->expectException(MissingValuesException::class);
        $this->request->init($definition);
    }

    /**
     * Wenn der Value fehlt, gibt es eine Exception
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_CustomField
     * @group SimpleApi_Product_Validator_CustomField_5
     *
     */
    public function invalidIsoCodeException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'boolean',
                'values' => [
                    'de_DE' => 1,
                ],
            ],
        ];
        $this->expectException(InvalidIsoCodeException::class);
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
     * @group SimpleApi_Product_Validator_CustomField
     * @group SimpleApi_Product_Validator_CustomField_6
     *
     */
    public function invalidCodeFormatException_KebabCase(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['custom_fields'] = [
            'ms-boolean' => [
                'type'   => 'boolean',
                'values' => [
                    'de-DE' => 1,
                ],
            ],
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
     * @group SimpleApi_Product_Validator_CustomField
     * @group SimpleApi_Product_Validator_CustomField_7
     *
     */
    public function invalidCodeFormatException_PascalCase(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['custom_fields'] = [
            'MsBoolean' => [
                'type'   => 'boolean',
                'values' => [
                    'de-DE' => 1,
                ],
            ],
        ];
        $this->expectException(InvalidCodeFormatException::class);
        $this->request->init($definition);
    }
}
