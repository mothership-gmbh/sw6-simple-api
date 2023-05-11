<?php

namespace MothershipSimpleApi\Tests\Service\Traits;

use JsonException;
use MothershipSimpleApi\Tests\Service\Processor\AbstractProcessorTest;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;

class CategoryProcessorTest extends AbstractProcessorTest
{
    /**
     * Produkt wird einer Kategorie zugeordnet
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Category
     * @group SimpleApi_Product_Processor_Category_1
     *
     * @throws JsonException
     */
    public function assignProductToCategory(): void
    {
        $this->createCategory('mothership_test', 'Test');
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['categories'] = ['mothership_test'];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals('mothership_test', $createdProduct->getCategories()->first()->getCustomFields()['code']);
        $this->assertEquals(1, $createdProduct->getCategories()->count());
    }

    /**
     * Produkt wird mehreren Kategorien zugeordnet.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Category
     * @group SimpleApi_Product_Processor_Category_2
     *
     * @throws JsonException
     */
    public function assignProductToMultipleCategories(): void
    {
        $this->createCategory('mothership_test_1', 'Test');
        $this->createCategory('mothership_test_2', 'Test');
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['categories'] = ['mothership_test_1', 'mothership_test_2'];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(2, $createdProduct->getCategories()->count());
    }

    /**
     * Produkt wird einer Kategorie zugeordnet und dann wieder entfernt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Category
     * @group SimpleApi_Product_Processor_Category_3
     *
     * @throws JsonException
     */
    public function assigendCategoryWillBeRemoved(): void
    {
        $this->createCategory('mothership_test_1', 'Test');
        $this->createCategory('mothership_test_2', 'Test');

        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['categories'] = ['mothership_test_1', 'mothership_test_2'];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(2, $createdProduct->getCategories()->count());

        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['categories'] = ['mothership_test_1'];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals(1, $createdProduct->getCategories()->count());
    }


    protected function createCategory(string $categoryCode, string $categoryName)
    {
        $context = $this->getContext();
        $category = $this->getRepository('category.repository');
        $entity = [
            'name' => $categoryName,
            'customFields' => [
                'code' => $categoryCode
            ]
        ];
        $category->upsert([$entity], $context);
    }
}
