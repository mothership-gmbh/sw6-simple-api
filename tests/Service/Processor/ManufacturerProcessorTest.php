<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;

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
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function createProductWithManufacturer(): void
    {
        $productDefinition = $this->getMinimalDefinition();
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
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function manufacturerWillBeUnassigned(): void
    {
        $productDefinition = $this->getMinimalDefinition();
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
