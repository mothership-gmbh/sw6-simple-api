<?php

namespace MothershipSimpleApi\Tests\Service\Traits;

use JsonException;
use MothershipSimpleApi\Tests\Service\Processor\AbstractProcessorTest;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;

class ManufacturerProcessorTest extends AbstractProcessorTest
{
    /**
     * Produkt wird einer Kategorie zugeordnet
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Manufacturer
     * @group SimpleApi_Product_Processor_Manufacturer_1
     *
     * @throws JsonException
     */
    public function createProductWithManufacturer(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['manufacturer'] = 'Aigner';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['manufacturer'], $createdProduct->getManufacturer()->getName());
    }

    /**
     * Produkt wird einer Kategorie zugeordnet
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Manufacturer
     * @group SimpleApi_Product_Processor_Manufacturer_2
     *
     * @throws JsonException
     */
    public function manufacturerWillBeUnassigned(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['manufacturer'] = 'Aigner';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['manufacturer'], $createdProduct->getManufacturer()->getName());

        // Hersteller wird entfernt
        $productDefinition['manufacturer'] = null;

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(null, $createdProduct->getManufacturer());
    }
}
