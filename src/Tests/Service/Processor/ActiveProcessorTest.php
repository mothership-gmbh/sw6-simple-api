<?php

namespace MothershipSimpleApi\Tests\Service\Traits;

use JsonException;
use MothershipSimpleApi\Tests\Service\Processor\AbstractProcessorTest;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;

class ActiveProcessorTest extends AbstractProcessorTest
{
    /**
     * Das Produkt wird aktiviert
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Active
     * @group SimpleApi_Product_Processor_Active_1
     *
     * @throws JsonException
     */
    public function productIsActive(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['active'] = true;

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['active'], $createdProduct->getActive());
    }

    /**
     * Das Produkt wird deaktiviert
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Active
     * @group SimpleApi_Product_Processor_Active_2
     *
     * @throws JsonException
     */
    public function productIsDesActivated(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['active'] = false;

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['active'], $createdProduct->getActive());
    }
}
