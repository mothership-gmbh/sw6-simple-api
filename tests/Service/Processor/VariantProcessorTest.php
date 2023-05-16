<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use MothershipSimpleApi\Service\Processor\PropertyGroupProcessor;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class VariantProcessorTest extends AbstractProcessorTest
{
    /**
     * Eine Variante ist so wie das Parent ein vollwertiges Produkt
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Variant
     * @group SimpleApi_Product_Processor_Variant_1
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function productWithOneVariantAndOption(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['variants'] = [
            [
                'sku'   => 'ms-123-S',
                'name'  => [
                    'en-GB' => 'T-Shirt S',
                ],
                'price' => [
                    // Wert in EUR
                    'EUR' => 20,
                ],
                'tax'   => 19,
                'stock' => 1,
                // sales channel auch?

                'properties' => [
                    'color' => ['red'],
                ],

                // Eine Variante muss eine Farbe gesetzt haben
                'axis'       => [
                    'color' => ['red'],
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertProductHasVariants($productDefinition, $createdProduct);
    }

    protected function assertProductHasVariants(array $productDefinition, ProductEntity $createdProduct): void
    {
        $this->assertNumberOfVariantsMatches($productDefinition, $createdProduct);
        $this->assertVariantIsAssignedToProduct($productDefinition, $createdProduct);
        $this->assertConfiguratorSettings($productDefinition, $createdProduct);
    }

    protected function assertNumberOfVariantsMatches(array $productDefinition, ProductEntity $createdProduct): void
    {
        $this->assertCount(count($productDefinition['variants']), $createdProduct->getChildren());
    }

    protected function assertVariantIsAssignedToProduct(array $productDefinition, ProductEntity $createdProduct): void
    {
        $parentId = Uuid::fromStringToHex($productDefinition['sku']);

        foreach ($productDefinition['variants'] as $variant) {
            $variantId = Uuid::fromStringToHex($variant['sku']);
            $this->assertEquals($variant['sku'], $createdProduct->getChildren()->getElements()[$variantId]->getProductNumber());

            $createdVariantProduct = $this->getProductBySku($variant['sku']);
            $this->assertEquals($parentId, $createdVariantProduct->getParentId());
        }
    }

    protected function assertConfiguratorSettings(array $productDefinition, ProductEntity $createdProduct): void
    {
        $parentId = Uuid::fromStringToHex($productDefinition['sku']);

        foreach ($productDefinition['variants'] as $variant) {
            foreach ($variant['axis'] as $propertyGroupCode => $propertyGroupOptions) {
                foreach ($propertyGroupOptions as $propertyOptionCode) {
                    $optionId = PropertyGroupProcessor::generatePropertyGroupOptionId($propertyGroupCode, $propertyOptionCode);
                    $combinedId = BitwiseOperations::xorHex($parentId, $optionId);

                    $this->assertEquals($parentId, $createdProduct->getConfiguratorSettings()->getElements()[$combinedId]->getProductId());
                    $this->assertEquals($optionId, $createdProduct->getConfiguratorSettings()->getElements()[$combinedId]->getOptionId());
                }
            }
        }
    }

    /**
     * Ein Produkt mit zwei Varianten
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Variant
     * @group SimpleApi_Product_Processor_Variant_2
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function productWithTwoVariants(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['variants'] = [
            [
                'sku'   => 'ms-123-S',
                'name'  => [
                    'en-GB' => 'T-Shirt S',
                ],
                'price' => [
                    // Wert in EUR
                    'EUR' => 20,
                ],
                'tax'   => 19,
                'stock' => 1,
                // sales channel auch?

                'properties' => [
                    'size' => ['S'],
                ],

                // Eine Variante muss eine Farbe gesetzt haben
                'axis'       => [
                    'size' => ['S'],
                ],
            ],
            [
                'sku'   => 'ms-123-L',
                'name'  => [
                    'en-GB' => 'T-Shirt L',
                ],
                'price' => [
                    // Wert in EUR
                    'EUR' => 20,
                ],
                'tax'   => 19,
                'stock' => 1,
                // sales channel auch?

                'properties' => [
                    'size' => ['L'],
                ],

                // Eine Variante muss eine Farbe gesetzt haben
                'axis'       => [
                    'size' => ['L'],
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertProductHasVariants($productDefinition, $createdProduct);
    }

    /**
     * Ein Produkt einer Variante aber zwei Optionen
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Variant
     * @group SimpleApi_Product_Processor_Variant_3
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function productWitOneVariantAndMultipleOptions(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['variants'] = [
            [
                'sku'   => 'ms-123-S',
                'name'  => [
                    'en-GB' => 'T-Shirt S',
                ],
                'price' => [
                    // Wert in EUR
                    'EUR' => 20,
                ],
                'tax'   => 19,
                'stock' => 1,
                // sales channel auch?

                'properties' => [
                    'size'  => ['S'],
                    'color' => ['red'],
                ],

                // Eine Variante muss eine Farbe gesetzt haben
                'axis'       => [
                    'size'  => ['S'],
                    'color' => ['red'],
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertProductHasVariants($productDefinition, $createdProduct);
    }

    /**
     * Ein Produkt mit zwei Varianten, aber eine Variante wird nachträglich gelöscht
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Variant
     * @group SimpleApi_Product_Processor_Variant_4
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function productWithTwoVariantsAndOneWillBeDeleted(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['variants'] = [
            [
                'sku'        => 'ms-123-S',
                'name'       => [
                    'en-GB' => 'T-Shirt S',
                ],
                'price'      => [
                    // Wert in EUR
                    'EUR' => 20,
                ],
                'tax'        => 19,
                'stock'      => 1,
                'properties' => [
                    'size' => ['S'],
                ],

                // Eine Variante muss eine Farbe gesetzt haben
                'axis'       => [
                    'size' => ['S'],
                ],
            ],
            [
                'sku'        => 'ms-123-L',
                'name'       => [
                    'en-GB' => 'T-Shirt L',
                ],
                'price'      => [
                    // Wert in EUR
                    'EUR' => 20,
                ],
                'tax'        => 19,
                'stock'      => 1,
                'properties' => [
                    'size' => ['L'],
                ],

                // Eine Variante muss eine Farbe gesetzt haben
                'axis'       => [
                    'size' => ['L'],
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertProductHasVariants($productDefinition, $createdProduct);

        // Nun wird eine weitere Variante entfernt, zumindest die Zuordnung
        $productDefinition['variants'] = [
            [
                'sku'        => 'ms-123-S',
                'name'       => [
                    'en-GB' => 'T-Shirt S',
                ],
                'price'      => [
                    // Wert in EUR
                    'EUR' => 20,
                ],
                'tax'        => 19,
                'stock'      => 1,
                'properties' => [
                    'size' => ['S'],
                ],

                // Eine Variante muss eine Farbe gesetzt haben
                'axis'       => [
                    'size' => ['S'],
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertProductHasVariants($productDefinition, $createdProduct);
    }
}
