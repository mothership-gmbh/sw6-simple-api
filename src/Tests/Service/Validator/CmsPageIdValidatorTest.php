<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\InvalidUuidException;

class CmsPageIdValidatorTest extends AbstractValidatorTest
{
    /**
     * Wenn die SKU fehlt, wird eine Exception geworfen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_CmsPageId
     * @group SimpleApi_Product_Validator_CmsPageId_1
     */
    public function invalidUuidWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['cms_page_id'] = 'invalid_uuid';

        $this->expectException(InvalidUuidException::class);
        $this->request->init($definition);
    }
}
