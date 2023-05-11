<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use JsonException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\MissingIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\MissingNameException;

class TranslationValidatorTest extends AbstractValidatorTest
{
    /**
     * Das Feld name ist ein Pflichtfeld
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Translation
     * @group SimpleApi_Product_Validator_Translation_1
     * @throws JsonException
     */
    public function missingNameWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        unset($definition['name']);

        $this->expectException(MissingNameException::class);
        $this->request->init($definition);
    }

    /**
     * Jedes der unterstützten Felder, zum Beispiel 'name', 'description', 'keywords', etc. benötigt
     * ein ISO-Key
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Translation
     * @group SimpleApi_Product_Validator_Translation_2
     * @throws JsonException
     */
    public function missingIsoCodesWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['name'] = 'invalid';

        $this->expectException(MissingIsoCodeException::class);
        $this->request->init($definition);
    }

    /**
     * Ein ungültiger Iso-Code führt zu einer Exception
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Translation
     * @group SimpleApi_Product_Validator_Translation_3
     * @throws JsonException
     */
    public function invalidIsoCodeWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['name'] = [
            'invalid-iso' => 'test'
        ];

        $this->expectException(InvalidIsoCodeException::class);
        $this->request->init($definition);
    }
}
