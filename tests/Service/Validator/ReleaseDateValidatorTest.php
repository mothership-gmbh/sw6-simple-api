<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\ReleaseDate\InvalidDateFormatException;

class ReleaseDateValidatorTest extends AbstractValidatorTest
{
    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_ReleaseDateValidator
     * @group SimpleApi_Product_Validator_ReleaseDateValidator_1
     *
     */
    public function invalidDataTypeWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['release_date'] = '/date(123456789)/';
        $this->expectException(InvalidDateFormatException::class);
        $this->request->init($definition);
    }
}
