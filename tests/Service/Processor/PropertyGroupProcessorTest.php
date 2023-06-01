<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Processor\PropertyGroupProcessor;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

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

    /**
     * Testet den Nebeneffekt, dass Änderungen an der property_group-Tabelle vorgenommen werden.
     *
     * Eine neue existierende propertyGroup wird erstellt.
     * Die bestehende Property muss anhand des codes gefunden werden, da es eine zufällig generierte UUID hat.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_PropertyGroup
     * @group SimpleApi_Product_Processor_PropertyGroup_4
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function propertyGroupWillBeCreated(): void
    {
        $propertyGroupRepository = $this->getRepository('property_group.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', 'ms-color'));
        // Das CustomField wurde korrekt erstellt.
        $this->assertEquals(0, $propertyGroupRepository->search($criteria, $this->getContext())->count());

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'ms-color' => ['red'],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das CustomField wurde korrekt erstellt.
        $this->assertEquals(1, $propertyGroupRepository->search($criteria, $this->getContext())->count());
    }

    /**
     * Testet den Nebeneffekt, dass Änderungen an der property_group-Tabelle vorgenommen werden.
     *
     * Eine existierende propertyGroup wird gefunden und nicht neu erstellt.
     * Die bestehende Property muss anhand des codes gefunden werden, da es eine zufällig generierte UUID hat.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_PropertyGroup
     * @group SimpleApi_Product_Processor_PropertyGroup_5
     */
    public function existingPropertyGroupWillNotBeCreatedAgain(): void
    {
        $propertyGroupRepository = $this->getRepository('property_group.repository');
        $propertyGroupRepository->create(
            [['name' => 'msColor', 'displayType' => 'text', 'sortingType' => 'alphanumeric', 'customFields' => ['code' => 'msColor']]],
            $this->getContext()
        );

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', 'msColor'));
        $this->assertEquals(1, $propertyGroupRepository->search($criteria, $this->getContext())->count());

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'msColor' => ['red'],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $this->assertEquals(1, $propertyGroupRepository->search($criteria, $this->getContext())->count());
    }
}
