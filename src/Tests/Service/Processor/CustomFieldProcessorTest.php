<?php

namespace MothershipSimpleApi\Tests\Service\Traits;

use JsonException;
use MothershipSimpleApi\Tests\Service\Processor\AbstractTranslationTestcase;
use Shopware\Core\Content\Product\ProductEntity;

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
     * @throws JsonException
     */
    public function oneCustomFieldWillBeAdded(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
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
     * @throws JsonException
     */
    public function twoCustomFieldsWillBeAdded(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
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
     * @throws JsonException
     */
    public function twoCustomFieldsForEveryLanguage(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
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
     * @throws JsonException
     */
    public function nameWillBeUpdated(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
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


        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['custom_fields'] = [
            'ms_boolean' => [
                'type'   => 'bool',
                'values' => [
                    'de-DE' => true,
                ],
            ]
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());

        $createdProduct = $this->getProductBySku($productDefinition['sku']);
        $this->assertCustomFieldExists($productDefinition['custom_fields']);
        $this->assertCustomFieldsSetInProduct($productDefinition['custom_fields'], $createdProduct);
    }

    protected function assertCustomFieldsSetInProduct(array $customFields, ProductEntity $createdProduct)
    {
        foreach ($customFields as $customFieldCode => $values) {
            $customField = array_filter($createdProduct->getTranslations()->getElements(), function($a) use ($customFieldCode) {
                $customFields = $a->getCustomFields();
                if (null !== $customFields) {
                    if (array_key_exists($customFieldCode, $customFields)) {
                        return $a;
                    }
                }
            });
            $this->assertNotEmpty($customField);
        }
    }

    protected function assertCustomFieldExists(array $customFields)
    {
        foreach ($customFields as $customFieldCode => $values) {
            $customField = $this->getCustomFieldByCode($customFieldCode);
            $this->assertEquals($customFieldCode, $customField->getName());
            $this->assertEquals($values['type'], $customField->getType());
        }
    }
}
