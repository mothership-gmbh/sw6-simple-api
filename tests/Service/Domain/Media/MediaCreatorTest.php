<?php

namespace MothershipSimpleApiTests\Service\Domain\Media;

use MothershipSimpleApi\Service\Domain\Media\MediaCreator;
use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Exception\ProductNotFoundException;
use MothershipSimpleApi\Service\Exception\PropertyGroupOptionNotFoundException;
use MothershipSimpleApi\Service\SimpleProductCreator;
use Shopware\Core\Content\Product\ProductEntity;

class MediaCreatorTest extends AbstractTestCase
{
    protected MediaCreator $mediaCreator;

    /**
     * Das einfache Produkt enthält nur die notwendigsten Informationen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Entity
     * @group SimpleApi_Product_Entity_1
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function createBasicProduct(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $context = $this->getContext();
        $this->mediaCreator->createEntity($productDefinition, $context);

        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertInstanceOf(ProductEntity::class, $createdProduct);
        $this->assertEquals($productDefinition['sku'], $createdProduct->getProductNumber());
        $this->assertEquals($productDefinition['tax'], $createdProduct->getTax()->getTaxRate());
        $this->assertEquals($productDefinition['price']['EUR']['regular'], $createdProduct->getPrice()->first()->getGross());
        $this->assertEquals($productDefinition['stock'], $createdProduct->getStock());
        $this->assertEquals($productDefinition['name']['en-GB'], $createdProduct->getName());
    }

    protected function setUp(): void
    {
        $this->mediaCreator = $this->getContainer()->get(MediaCreator::class);

        $product = $this->getMinimalDefinition();
        $this->deleteProductBySku($product['sku']);
    }
}
