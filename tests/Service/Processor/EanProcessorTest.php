<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;

class EanProcessorTest extends AbstractProcessorTest
{
    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Ean
     * @group SimpleApi_Product_Processor_Ean_1
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function createProductWithEan(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['ean'] = '1234567891011';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['ean'], $createdProduct->getEan());
    }

    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Ean
     * @group SimpleApi_Product_Processor_Ean_2
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function eanWillBeRemoved(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['ean'] = '1234567891011';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Ean wird entfernt
        $productDefinition['ean'] = null;

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(null, $createdProduct->getEan());
    }
}
