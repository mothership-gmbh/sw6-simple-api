<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;

class ManufacturerNumberProcessorTest extends AbstractProcessorTest
{
    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_ManufacturerNumber
     * @group SimpleApi_Product_Processor_ManufacturerNumber_1
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function createProductWithManufacturerNumber(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['manufacturer_number'] = '123-Testprodukt-ABC';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['manufacturer_number'], $createdProduct->getManufacturerNumber());
    }

    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_ManufacturerNumber
     * @group SimpleApi_Product_Processor_ManufacturerNumber_2
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function manufacturerNumberWillBeRemoved(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['manufacturer_number'] = '123-Testprodukt-ABC';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Ean wird entfernt
        $productDefinition['manufacturer_number'] = null;

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(null, $createdProduct->getManufacturerNumber());
    }
}
