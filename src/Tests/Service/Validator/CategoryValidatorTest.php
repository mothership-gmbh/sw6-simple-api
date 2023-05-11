<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\Category\InvalidDataTypeException;

class CategoryValidatorTest extends AbstractValidatorTest
{
    /**
     * Das Feld name ist ein Pflichtfeld
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Category
     * @group SimpleApi_Product_Validator_Category_1
     */
    public function missingNameWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['categories'] = 'test';

        $this->expectException(InvalidDataTypeException::class);
        $this->request->init($definition);
    }
}
