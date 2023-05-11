<?php

namespace MothershipSimpleApi\Tests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;

class LayoutProcessorTest extends AbstractProcessorTest
{
    /**
     * Das Produkt bekommt eine Layout-Zuordnung
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Layout
     * @group SimpleApi_Product_Processor_Layout_1
     *
     * @throws InvalidTaxValueException
     * @throws InvalidCurrencyCodeException
     */
    public function assignedToHeadlessChannel(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $defaultPageLayout = $this->getLayoutIdByType('product_detail');

        $productDefinition['cms_page_id'] = $defaultPageLayout->getId();

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['cms_page_id'], $createdProduct->getCmsPageId());
    }
}
