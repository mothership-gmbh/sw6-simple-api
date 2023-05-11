<?php

namespace MothershipSimpleApi\Tests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;

class TranslationProcessorTest extends AbstractTranslationTestcase
{
    /**
     * Der Name wird hinzugefügt
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Translation
     * @group SimpleApi_Product_Processor_Translation_1
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function nameWillBeAdded(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['name'] = [
            'en-GB' => 'T-Shirt EN',
            'de-DE' => 'T-Shirt DE',
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertEquals($productDefinition['name']['de-DE'], $translationEntityDe->getName());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertEquals($productDefinition['name']['en-GB'], $translationEntityEn->getName());
    }

    /**
     * Der Name wird aktualisiert
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Translation
     * @group SimpleApi_Product_Processor_Translation_2
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function nameWillBeUpdated(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['name'] = [
            'en-GB' => 'T-Shirt EN',
            'de-DE' => 'T-Shirt DE',
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertEquals($productDefinition['name']['de-DE'], $translationEntityDe->getName());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertEquals($productDefinition['name']['en-GB'], $translationEntityEn->getName());

        $productDefinition['name'] = [
            'en-GB' => 'T-Shirt EN - UPDATED',
            'de-DE' => 'T-Shirt DE',
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertEquals($productDefinition['name']['en-GB'], $translationEntityEn->getName());
    }

    /**
     * Der Name wird aktualisiert
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Translation
     * @group SimpleApi_Product_Processor_Translation_3
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function descriptionWillBeAdded(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['description'] = [
            'en-GB' => 'Description EN',
            'de-DE' => 'Beschreibung DE',
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertEquals($productDefinition['description']['de-DE'], $translationEntityDe->getDescription());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertEquals($productDefinition['description']['en-GB'], $translationEntityEn->getDescription());
    }

    /**
     * Das Feld 'keywords' wird aktualisiert
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Translation
     * @group SimpleApi_Product_Processor_Translation_4
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function keywordWillBeUpdated(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['keywords'] = [
            'en-GB' => 'Keyword EN',
            'de-DE' => 'Keyword DE',
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertEquals($productDefinition['keywords']['de-DE'], $translationEntityDe->getKeywords());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertEquals($productDefinition['keywords']['en-GB'], $translationEntityEn->getKeywords());
    }

    /**
     * Das Feld 'meta-title' wird aktualisiert
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Translation
     * @group SimpleApi_Product_Processor_Translation_5
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function metaTitleWillBeAdded(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['meta_title'] = [
            'en-GB' => 'Keyword EN',
            'de-DE' => 'Keyword DE',
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertEquals($productDefinition['meta_title']['de-DE'], $translationEntityDe->getMetaTitle());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertEquals($productDefinition['meta_title']['en-GB'], $translationEntityEn->getMetaTitle());
    }

    /**
     * Das Feld 'meta-description' wird aktualisiert
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Translation
     * @group SimpleApi_Product_Processor_Translation_6
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function metaDescriptionWillBeAdded(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['meta_description'] = [
            'en-GB' => 'Description EN',
            'de-DE' => 'Description DE',
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $translationEntityDe = $this->getTranslationElementByIsoCode($createdProduct, 'de-DE');
        $this->assertEquals($productDefinition['meta_description']['de-DE'], $translationEntityDe->getMetaDescription());

        // Für EN gibt es noch keine Übersetzungen
        $translationEntityEn = $this->getTranslationElementByIsoCode($createdProduct, 'en-GB');
        $this->assertEquals($productDefinition['meta_description']['en-GB'], $translationEntityEn->getMetaDescription());
    }
}
