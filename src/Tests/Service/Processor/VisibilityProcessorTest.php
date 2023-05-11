<?php

namespace MothershipSimpleApi\Tests\Service\Traits;

use JsonException;
use MothershipSimpleApi\Tests\Service\Processor\AbstractProcessorTest;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;

class VisibilityProcessorTest extends AbstractProcessorTest
{
    CONST POS_COVER_IMAGE = 0;

    /**
     * Das Produkt wird dem Headless-Kanal zugeordnet
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Visibility
     * @group SimpleApi_Product_Processor_Visibility_1
     *
     * @throws JsonException
     */
    public function assignedToHeadlessChannel(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['sales_channel'] = [
            "Headless" => "all"
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(ProductVisibilityDefinition::VISIBILITY_ALL, $createdProduct->getVisibilities()->first()->getVisibility());
        $this->assertEquals(1, $createdProduct->getVisibilities()->count());
    }

    /**
     * Das Produkt wird dem Headless-Kanal und dem Storefront-Kanal zugeordnet
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Visibility
     * @group SimpleApi_Product_Processor_Visibility_2
     *
     * @throws JsonException
     */
    public function assignedToHeadlessAndStoreFrontChannel(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['sales_channel'] = [
            "Headless"   => "all",
            "Storefront" => "all",
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(ProductVisibilityDefinition::VISIBILITY_ALL, $createdProduct->getVisibilities()->getAt(0)->getVisibility());
        $this->assertEquals(ProductVisibilityDefinition::VISIBILITY_ALL, $createdProduct->getVisibilities()->getAt(1)->getVisibility());
        $this->assertEquals(2, $createdProduct->getVisibilities()->count());
    }

    /**
     * Das Produkt wird dem Headless-Kanal zugeordnet und im nächsten Schritt wieder entfernt
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Visibility
     * @group SimpleApi_Product_Processor_Visibility_3
     *
     * @throws JsonException
     */
    public function assignedToHeadlessChannelAndRemovedAfterwards(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['sales_channel'] = [
            "Headless"   => "all"
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(ProductVisibilityDefinition::VISIBILITY_ALL, $createdProduct->getVisibilities()->getAt(0)->getVisibility());
        $this->assertEquals(1, $createdProduct->getVisibilities()->count());

        unset($productDefinition['sales_channel']);
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(0, $createdProduct->getVisibilities()->count());
    }
}
