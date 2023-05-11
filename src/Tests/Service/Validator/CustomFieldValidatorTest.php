<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingTypeDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingValuesException;

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
}
