<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Processor\PropertyGroupProcessor;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

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
     * Eine neue propertyGroup wird erstellt.
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
        $criteria->addFilter(new EqualsFilter('customFields.code', 'msColor'));
        // Aktuell sollte es noch keine PropertyGroup geben
        $this->assertCount(0, $propertyGroupRepository->search($criteria, $this->getContext())->getEntities()->getElements());

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'msColor' => ['red'],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Die PropertyGroup wurde korrekt erstellt.
        $this->assertCount(1, $propertyGroupRepository->search($criteria, $this->getContext())->getEntities()->getElements());
    }

    /**
     * Testet den Nebeneffekt, dass Änderungen an der property_group-Tabelle vorgenommen werden.
     *
     * Eine existierende PropertyGroup wird gefunden und nicht neu erstellt.
     * Die bestehende PropertyGroup muss anhand des codes gefunden werden, da sie eine zufällig generierte UUID hat.
     *
     * Ebenso wird eine existierende PropertyGroupOption gefunden und nicht neu erstellt.
     * Die bestehende PropertyGroupOption muss anhand des codes gefunden werden, da sie eine zufällig generierte UUID hat.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_PropertyGroup
     * @group SimpleApi_Product_Processor_PropertyGroup_5
     */
    public function existingPropertyWillNotBeCreatedAgain(): void
    {
        // Zunächst hinterlegen wir schonmal eine PropertyGroup samt PropertyGroupOption
        $propertyGroupRepository = $this->getRepository('property_group.repository');
        $propertyGroupId = Uuid::randomHex();
        $propertyGroupRepository->create([
            [
                'id'           => $propertyGroupId,
                'translations' => [
                    $this->getContext()->getLanguageId() => [
                        'name'         => 'ms_color',
                        'customFields' => ['code' => 'ms_color'],
                    ],
                ],
            ],
        ], $this->getContext());
        $propertyGroupOptionRepository = $this->getRepository('property_group_option.repository');
        $propertyGroupOptionId = Uuid::randomHex();
        $propertyGroupOptionRepository->create([
            [
                'id'           => $propertyGroupOptionId,
                'translations' => [
                    $this->getContext()->getLanguageId() => [
                        'name'         => 'red_color',
                        'customFields' => ['code' => 'red_color'],
                    ],
                ],
                'groupId'      => $propertyGroupId,
            ],
        ], $this->getContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', 'ms_color'));
        $this->assertCount(
            1,
            $propertyGroupRepository->search($criteria, $this->getContext())->getEntities()->getElements()
        );
        $optionCriteria = new Criteria();
        $optionCriteria->addFilter(new EqualsFilter('customFields.code', 'red'), new EqualsFilter('groupId', $propertyGroupId));
        $this->assertCount(
            1,
            $propertyGroupOptionRepository->search($optionCriteria, $this->getContext())->getEntities()->getElements()
        );

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['properties'] = [
            'ms_color' => ['red_color'],
        ];
        /*
        Das Erstellen des Produkts kann unter Umständen zur Folge haben, dass neue Properties erstellt werden
        {@see PropertyGroupProcessorTest::propertyGroupWillBeCreated}.
        Das soll hier nicht passieren, weil die Property bereits in der db vorhanden ist.
        */
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $this->assertCount(
            1,
            $propertyGroupRepository->search($criteria, $this->getContext())->getEntities()->getElements()
        );
        $this->assertCount(
            1,
            $propertyGroupOptionRepository->search($optionCriteria, $this->getContext())->getEntities()->getElements()
        );
    }
}
