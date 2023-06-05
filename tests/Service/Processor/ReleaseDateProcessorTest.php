<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;

class ReleaseDateProcessorTest extends AbstractProcessorTest
{
    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_ReleaseDate
     * @group SimpleApi_Product_Processor_ReleaseDate_1
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function createProductWithReleaseDate(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['release_date'] = '2023-01-01 00:00:00';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(
            date_create_immutable_from_format('Y-m-d H:i:s', $productDefinition['release_date']),
            $createdProduct->getReleaseDate()
        );
    }

    /**
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_ReleaseDate
     * @group SimpleApi_Product_Processor_ReleaseDate_2
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function releaseDateWillBeRemoved(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['release_date'] = '2023-01-01 00:00:00';

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Ean wird entfernt
        $productDefinition['release_date'] = null;

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(null, $createdProduct->getReleaseDate());
    }
}
