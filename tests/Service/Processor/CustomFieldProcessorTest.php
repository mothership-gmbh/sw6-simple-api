<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

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
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
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
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
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
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
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
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
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
     */
    public function existingCustomFieldWithGeneratedUUIDWillBeFound(): void
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

        $customFieldRepository = $this->getRepository('custom_field.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'ms_boolean'));
        // Das CustomField wurde nur 1 Mal erstellt.
        $this->assertEquals(1, $customFieldRepository->search($criteria, $this->getContext())->count());
    }

    /**
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
     */
    public function existingCustomFieldWithRandomUUIDWillBeFound(): void
    {
        $this->cleanCustomFields();

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

        $customFieldRepository = $this->getRepository('custom_field.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'ms_boolean'));
        // Das CustomField wurde nur 1 Mal erstellt.
        $this->assertEquals(1, $customFieldRepository->search($criteria, $this->getContext())->count());
    }
}
