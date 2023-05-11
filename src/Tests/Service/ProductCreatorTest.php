<?php

namespace MothershipSimpleApi\Tests\Service;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\SimpleProductCreator;
use Shopware\Core\Content\Product\ProductEntity;

class ProductCreatorTest extends AbstractTestCase
{
    protected SimpleProductCreator $simpleProductCreator;

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
     * @throws InvalidTaxValueException
     */
    public function createBasicProduct(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $context = $this->getContext();
        $this->simpleProductCreator->createEntity($productDefinition, $context);

        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertInstanceOf(ProductEntity::class, $createdProduct);
        $this->assertEquals($productDefinition['sku'], $createdProduct->getProductNumber());
        $this->assertEquals($productDefinition['tax'], $createdProduct->getTax()->getTaxRate());
        $this->assertEquals($productDefinition['price']['EUR'], $createdProduct->getPrice()->first()->getGross());
        $this->assertEquals($productDefinition['stock'], $createdProduct->getStock());
        $this->assertEquals($productDefinition['name']['en-GB'], $createdProduct->getName());
    }

    /**
     * Die Steuer-Id wird anhand der in der Tabelle 'tax' hinterlegten Werte identifiziert.
     * Falls also ein ungültiger Steuersatz übergeben wird, so folgt darauf eine Exception.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Entity
     * @group SimpleApi_Product_Entity_2
     * @throws InvalidTaxValueException
     * @throws InvalidCurrencyCodeException
     */
    public function invalidTaxWillThrowException(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['tax'] = 25; // Es wird ein Steuersatz gesetzt, der nicht existiert.
        $this->expectException(InvalidTaxValueException::class);
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
    }

    /**
     * Die Währung wird über die Tabelle 'currency' identifiziert. Da es die invalide Währung
     * nicht gibt, wird eine Exception geworfen.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Entity
     * @group SimpleApi_Product_Entity_3
     * @throws InvalidTaxValueException
     */
    public function invalidCurrencyWillThrowException(): void
    {
        $productDefinition = $this->getMinimalDefinition();

        // Wir setzen eine Währung, die nicht existiert.
        $productDefinition['price'] = [
            'INVALID_CURRENCY_CODE' => 50,
        ];
        $this->expectException(InvalidCurrencyCodeException::class);
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
    }

    /**
     * Anlage von einem Produkt mit unterschiedlichen Währungen ist auch möglich
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Entity
     * @group SimpleApi_Product_Entity_4
     * @throws InvalidTaxValueException
     * @throws InvalidCurrencyCodeException
     */
    public function productHasMultipleCurrencies(): void
    {
        $productDefinition = $this->getMinimalDefinition();

        // Es wird GBP hinzugefügt.
        $productDefinition['price']['GBP'] = 50;
        $productDefinition['price']['EUR'] = 50;

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals($productDefinition['price']['EUR'], $createdProduct->getPrice()->getAt(0)->getGross());
        $this->assertEquals($productDefinition['price']['GBP'], $createdProduct->getPrice()->getAt(1)->getGross());
    }

    /**
     * Setzt explizit die Sichtbarkeit von dem Produkt.
     *
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Entity
     * @group SimpleApi_Product_Entity_5
     * @throws InvalidCurrencyCodeException
     * @throws InvalidTaxValueException
     */
    public function addSalesChannel(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        // Das Produkt wird einem Verkaufskanal zugeordnet
        $productDefinition['sales_channel'] = [
            'default' => 'all', // hier wären auch andere Werte möglich
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(30, $createdProduct->getVisibilities()->first()->getVisibility());
    }

    protected function setUp(): void
    {
        $this->simpleProductCreator = $this->getContainer()->get(SimpleProductCreator::class);

        $product = $this->getMinimalDefinition();
        $this->deleteProductBySku($product['sku']);
    }
}
