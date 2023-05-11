<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use JsonException;
use MothershipSimpleApi\Service\Validator\Exception\Active\InvalidStateException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingTypeDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingValuesException;

class ActiveValidatorTest extends AbstractValidatorTest
{
    /**
     * Überprüft, ob ein Produkt mit aktivem Status validiert wird
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_ActiveValidator
     * @group SimpleApi_Product_Validator_ActiveValidator_1
     *
     * @throws JsonException
     */
    public function invalidIsoCodeSchemaWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['active'] = 'Aktiv';
        $this->expectException(InvalidStateException::class);
        $this->request->init($definition);
    }
}
