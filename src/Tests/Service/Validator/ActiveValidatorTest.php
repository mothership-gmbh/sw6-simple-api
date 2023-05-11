<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\Active\InvalidStateException;

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
     */
    public function invalidIsoCodeSchemaWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['active'] = 'Aktiv';
        $this->expectException(InvalidStateException::class);
        $this->request->init($definition);
    }
}
