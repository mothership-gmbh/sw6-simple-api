<?php

namespace MothershipSimpleApi\Tests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Processor\PropertyGroupProcessor;
use Shopware\Core\Content\Product\ProductEntity;

class PropertyGroupProcessorTest extends AbstractProcessorTest
{
    /**
     * Das Produkt hat eine neue Property-Group 'color', die noch nicht
     * im System existiert. Bei der Anlage wird das geprüft und dann angelegt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_PropertyGroup
     * @group SimpleApi_Product_Processor_PropertyGroup_1
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function productWithOneOption(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'color' => ['red'],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertProperties($productDefinition['properties']);
        $this->assertPropertyOptionIsAssignedToProduct($createdProduct, $productDefinition['properties']);
    }

    /**
     * Helper, der prüft, dass alle Eigenschaften korrekt angelegt wurden.
     *
     * @param array $properties
     *
     * @return void
     */
    protected function assertProperties(array $properties): void
    {
        foreach ($properties as $propertyGroupCode => $propertyGroupOptions) {
            $propertyGroup = $this->getPropertyGroupByCode($propertyGroupCode);
            $this->assertEquals($propertyGroupCode, $propertyGroup->getName());

            foreach ($propertyGroupOptions as $propertyGroupOptionCode) {
                $propertyGroupOption = $this->getPropertyGroupOptionByCode($propertyGroupOptionCode);
                $this->assertEquals($propertyGroupOptionCode, $propertyGroupOption->getName());
            }
        }
    }

    protected function assertPropertyOptionIsAssignedToProduct(ProductEntity $productEntity, array $properties): void
    {
        $assignedProperties = $productEntity->getProperties()->getIds();

        foreach ($properties as $propertyGroupCode => $propertyGroupOptions) {
            foreach ($propertyGroupOptions as $propertyGroupOptionCode) {

                $propertyGroupOptionId = PropertyGroupProcessor::generatePropertyGroupOptionId($propertyGroupCode, $propertyGroupOptionCode);
                $this->assertArrayHasKey($propertyGroupOptionId, $assignedProperties);
            }
        }
    }

    /**
     * Das Produkt hat zwei Optionen, 'red' und 'blue' und eine Größe 'size'
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_PropertyGroup
     * @group SimpleApi_Product_Processor_PropertyGroup_2
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function productWithTwoOptions(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'color' => ['red', 'blue'],
            'size'  => ['l', 'xl'],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertProperties($productDefinition['properties']);
        $this->assertPropertyOptionIsAssignedToProduct($createdProduct, $productDefinition['properties']);
    }

    /**
     * Dem Produkt werden nun einige Optionen hinzugefügt und danach wieder entfernt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_PropertyGroup
     * @group SimpleApi_Product_Processor_PropertyGroup_3
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function productPropertiesWillBeRemoved(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'color' => ['red', 'blue'],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertProperties($productDefinition['properties']);
        $this->assertPropertyOptionIsAssignedToProduct($createdProduct, $productDefinition['properties']);

        // Es gibt zwei Optionen
        $this->assertCount(2, $createdProduct->getProperties());

        // Und hier werden die Properties wieder entfernt
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'color' => ['red'],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        // es gibt nur noch eine Option
        $this->assertCount(1, $createdProduct->getProperties());
    }
}
