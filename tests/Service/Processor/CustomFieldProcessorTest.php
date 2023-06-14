<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Exception\ProductNotFoundException;
use MothershipSimpleApi\Service\Exception\PropertyGroupOptionNotFoundException;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class CustomFieldProcessorTest extends AbstractTranslationTestcase
{
    /**
     * Ein einzelnes Custom-Field 'ms_boolean' wird hinzugefügt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_1
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function oneCustomFieldWillBeAdded(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => true,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertCustomFieldExists($productDefinition['custom_fields']);
        $this->assertCustomFieldsSetInProduct($productDefinition['custom_fields'], $createdProduct);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertCount(1, $translationEntityDe->getCustomFields());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertNull($translationEntityEn->getCustomFields());
    }

    protected function assertCustomFieldExists(array $customFields): void
    {
        foreach ($customFields as $customFieldCode => $values) {
            $customField = $this->getCustomFieldByCode($customFieldCode);
            $this->assertEquals($customFieldCode, $customField->getName());
            $this->assertEquals($values['type'], $customField->getType());
        }
    }

    protected function assertCustomFieldsSetInProduct(array $customFields, ProductEntity $createdProduct): void
    {
        foreach ($customFields as $customFieldCode => $values) {
            $customField = array_filter($createdProduct->getTranslations()->getElements(), static function ($a) use ($customFieldCode) {
                $customFields = $a->getCustomFields();
                if ((null !== $customFields) && array_key_exists($customFieldCode, $customFields)) {
                    return $a;
                }
                return null;
            });
            $this->assertNotEmpty($customField);
        }
    }

    /**
     * Es wird zzgl. noch ein weiteres Custom-Field "ms_integer" hinzugefügt
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_2
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function twoCustomFieldsWillBeAdded(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => true,
                ],
            ],
            'ms_integer' => [
                'type'   => 'int',
                'values' => [
                    'de-DE' => 1,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertCustomFieldExists($productDefinition['custom_fields']);
        $this->assertCustomFieldsSetInProduct($productDefinition['custom_fields'], $createdProduct);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertCount(2, $translationEntityDe->getCustomFields());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertNull($translationEntityEn->getCustomFields());
    }

    /**
     * Die Translations werden auch für eine weitere Sprache en-GB hinzugefügt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_3
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function twoCustomFieldsForEveryLanguage(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => true,
                    'en-GB' => true,
                ],
            ],
            'ms_integer' => [
                'type'   => 'int',
                'values' => [
                    'de-DE' => 1,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertCustomFieldExists($productDefinition['custom_fields']);
        $this->assertCustomFieldsSetInProduct($productDefinition['custom_fields'], $createdProduct);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertCount(2, $translationEntityDe->getCustomFields());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertCount(1, $translationEntityEn->getCustomFields());
    }

    /**
     * Es wird zzgl. noch ein weiteres Custom-Field "ms_integer" hinzugefügt
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_4
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function nameWillBeUpdated(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => true,
                ],
            ],
            'ms_integer' => [
                'type'   => 'int',
                'values' => [
                    'de-DE' => 1,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertCustomFieldExists($productDefinition['custom_fields']);
        $this->assertCustomFieldsSetInProduct($productDefinition['custom_fields'], $createdProduct);


        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => true,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertCustomFieldExists($productDefinition['custom_fields']);
        $this->assertCustomFieldsSetInProduct($productDefinition['custom_fields'], $createdProduct);
    }

    /**
     * Testet den Nebeneffekt, dass Änderungen an der custom_field-Tabelle vorgenommen werden.
     *
     * Ein existierendes customField wird gefunden und nicht neu erstellt.
     * CustomField kann anhand der erwarteten nachvollziehbaren UUID gefunden werden, weil es neu erstellt
     * wurde mit generierter UUID.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_5
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function existingCustomFieldWithGeneratedUUIDWillBeFound(): void
    {
        $customFieldRepository = $this->getRepository('custom_field.repository');
        $criteria = new Criteria();
        $this->assertEquals(0, $customFieldRepository->search($criteria, $this->getContext())->count());

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => true,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $criteria->addFilter(new EqualsFilter('name', 'ms_boolean'));
        // Das CustomField wurde neu erstellt.
        $this->assertCount(1, $customFieldRepository->search($criteria, $this->getContext()));

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => false,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das CustomField wurde nur 1 Mal erstellt.
        $this->assertEquals(1, $customFieldRepository->search($criteria, $this->getContext())->count());
    }

    /**
     * Testet den Nebeneffekt, dass Änderungen an der custom_field-Tabelle vorgenommen werden.
     *
     * Ein existierendes customField wird gefunden und nicht neu erstellt.
     * Das bestehende CustomField muss anhand des codes gefunden werden, da es eine zufällig generierte UUID hat.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_6
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function existingCustomFieldWithRandomUUIDWillBeFound(): void
    {
        $customFieldRepository = $this->getRepository('custom_field.repository');
        $customFieldRepository->create([['name' => 'ms_boolean', 'type' => 'bool']], $this->getContext());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'ms_boolean'));
        // Das CustomField wurde korrekt erstellt.
        $this->assertEquals(1, $customFieldRepository->search($criteria, $this->getContext())->count());

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => false,
                ],
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das CustomField wurde nur 1 Mal erstellt.
        $this->assertEquals(1, $customFieldRepository->search($criteria, $this->getContext())->count());
    }

    /**
     * Testet den Nebeneffekt, dass Änderungen an den custom_field_set- & Custom_field_set_relation-Tabellen vorgenommen werden.
     *
     * Gibt es ein CustomField noch nicht, muss dieses neu erstellt werden.
     * Damit das neue CustomField aber auch mit dem Produkt verknüpft wird, muss ein neues customFieldSet & eine
     * customFieldSetRelation erstellt werden.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_7
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function newCustomFieldSetWillBeCreatedIfCustomFieldDoesntExist(): void
    {
        $customFieldSetRepository = $this->getRepository('custom_field_set.repository');
        $setName = 'product_details_simple_api';
        $setId = Uuid::fromStringToHex($setName);

        $criteria = new Criteria();

        // Die custom_field_set & custom_field_set_relation-Tabellen sollten leer sein.
        $this->assertCount(0, $customFieldSetRepository->search($criteria, $this->getContext()));
        $customFieldSetRelationRepository = $this->getRepository('custom_field_set_relation.repository');
        $this->assertCount(0, $customFieldSetRelationRepository->search($criteria, $this->getContext()));

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => false,
                ],
            ],
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das CustomFieldSet wurde erstellt.
        $criteria->addFilter(new EqualsFilter('name', $setName));
        $customFieldSets = $customFieldSetRepository->search($criteria, $this->getContext());
        $this->assertCount(1, $customFieldSets);
        // Das erstellte CustomFieldSet hat die generierte UUID & ist aktiv.
        $this->assertEquals($setId, $customFieldSets->first()->getId());
        $this->assertEquals(1, $customFieldSets->first()->isActive());

        // Die CustomFieldSetRelation wurde erstellt.
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFieldSetId', $setId));
        $customFieldSetRelations = $customFieldSetRelationRepository->search($criteria, $this->getContext());
        $this->assertCount(1, $customFieldSetRelations);
        // Die CustomFieldSetRelation enthält die erwarteten Werte.
        $customFieldSetRelationId = BitwiseOperations::xorHex(Uuid::fromStringToHex('product'), $setId);
        $this->assertEquals($customFieldSetRelationId, $customFieldSetRelations->first()->getId());
        $this->assertEquals($setId, $customFieldSetRelations->first()->getCustomFieldSetId());
        $this->assertEquals('product', $customFieldSetRelations->first()->getEntityName());
    }

    /**
     * Testet den Nebeneffekt, dass Änderungen an den custom_field_set- & Custom_field_set_relation-Tabellen vorgenommen werden.
     *
     * Gibt es ein CustomField noch nicht, muss dieses neu erstellt werden.
     * Damit das neue CustomField aber auch mit dem Produkt verknüpft wird, muss ein neues customFieldSet & eine
     * customFieldSetRelation erstellt werden.
     * Falls das CustomFieldSet aber schonmal durch die API erstellt wurde, soll es nicht noch einmal erstellt werden.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_8
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function existingCustomFieldSetWillBeUsed(): void
    {
        $customFieldSetRepository = $this->getRepository('custom_field_set.repository');
        $customFieldSetRelationRepository = $this->getRepository('custom_field_set_relation.repository');
        $setName = 'product_details_simple_api';
        $setId = Uuid::fromStringToHex($setName);

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => false,
                ],
            ],
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Wir erstellen direkt noch ein neues Produkt mit einem customField, das es noch nicht gibt.
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_integer' => [
                'type'   => 'int',
                'values' => [
                    'de-DE' => 1,
                ],
            ],
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das CustomFieldSet wurde nur 1 Mal erstellt.
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $setName));
        $this->assertCount(1, $customFieldSetRepository->search($criteria, $this->getContext()));
        // Die CustomFieldSetRelation wurde nur 1 Mal erstellt.
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFieldSetId', $setId));
        $this->assertCount(1, $customFieldSetRelationRepository->search($criteria, $this->getContext()));
    }

    /**
     * Testet den Nebeneffekt, dass Änderungen an den custom_field_set-Tabelle vorgenommen werden.
     *
     * Damit ein CustomField erstellt und auch in den Produktdaten angezeigt werden kann, muss ein CustomFieldSet erstellt werden.
     * Das neu erstellte CustomFieldSet bekommt als Label einen Standardwert.
     * Dieser Standardwert wird für jede Sprache gesetzt, für die auch ein Wert im CustomField übergeben wurde.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_9
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function customFieldSetWillHaveLabels(): void
    {
        $customFieldSetRepository = $this->getRepository('custom_field_set.repository');

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => false,
                    'en-GB' => false
                ],
            ],
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das CustomFieldSet hat die erwarteten Labels.
        $criteria = new Criteria();
        $this->assertEquals(['label' => ['de-DE' => 'Details (Simple API)', 'en-GB' => 'Details (Simple API)']],
            $customFieldSetRepository->search($criteria, $this->getContext())->first()->getConfig());
    }

    /**
     * Wird im Payload nicht explizit übergeben welche Labels das customField haben soll,
     * wird der customField-Code als Label übernommen.
     * Das ist wichtig, weil in der Shopware Administration ein customField ohne Label nicht so gut dargestellt werden kann.
     * Das Label wird in jeder Sprache gesetzt, für die auch ein Wert beim CustomField übergeben wurde.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_10
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function customFieldCodeWillBeSetAsLabel(): void
    {
        $customFieldRepository = $this->getRepository('custom_field.repository');

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => false,
                    'en-US' => false
                ],
            ],
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das neue CustomField enthält die erwarteten Labels.
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'ms_boolean'));
        $customFields = $customFieldRepository->search($criteria, $this->getContext());
        $this->assertEquals([
            'type'            => 'switch',
            'componentName'   => 'sw-field',
            'customFieldType' => 'switch',
            'label' => ['de-DE' => 'ms_boolean', 'en-US' => 'ms_boolean']
        ], $customFields->first()->getConfig());
    }

    /**
     * Im Payload kann explizit übergeben werden welche Labels das customField haben soll.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_CustomField
     * @group SimpleApi_Product_Processor_CustomField_11
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function customFieldLabelWillBeSetAsLabel(): void
    {
        $customFieldRepository = $this->getRepository('custom_field.repository');

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => false
                ],
                'labels' => [
                    'de-DE' => 'Boolean Feld',
                    'en-GB' => 'Boolean Field'
                ]
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        // Das neue CustomField enthält die erwarteten Labels.
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'ms_boolean'));
        $customFields = $customFieldRepository->search($criteria, $this->getContext());
        $this->assertEquals([
            'type'            => 'switch',
            'componentName'   => 'sw-field',
            'customFieldType' => 'switch',
            'label' => ['de-DE' => 'Boolean Feld', 'en-GB' => 'Boolean Field']
        ], $customFields->first()->getConfig());
    }
}
