<?php

namespace MothershipSimpleApi\Tests\Service\Traits;

use JsonException;
use MothershipSimpleApi\Service\SimpleProductCreator;
use MothershipSimpleApi\Tests\Service\AbstractTestCase;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;

class ImageProcessorTest extends AbstractTestCase
{
    CONST POS_COVER_IMAGE = 0;

    protected SimpleProductCreator $simpleProductCreator;

    protected function setUp(): void
    {
        $this->simpleProductCreator = $this->getContainer()->get(\MothershipSimpleApi\Service\SimpleProductCreator::class);
        $this->cleanMedia();
        $this->cleanProduct();
    }

    /**
     * Fügt dem Produkt ein einzelnes Bild hinzu
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_1
     *
     * @throws JsonException
     */
    public function singleImage(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true
            ]
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals('50x50', $createdProduct->getMedia()->getAt(self::POS_COVER_IMAGE)->getMedia()->getFileName());
        $this->assertEquals('png', $createdProduct->getMedia()->getAt(self::POS_COVER_IMAGE)->getMedia()->getFileExtension());
        // Es gibt auch nur ein Bild
        $this->assertEquals(1, $createdProduct->getMedia()->count());
    }

    /**
     * Die Extension fehlt
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_2
     *
     * @throws JsonException
     */
    public function multipleImages(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
            ]
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals('50x50', $createdProduct->getMedia()->getAt(self::POS_COVER_IMAGE)->getMedia()->getFileName());
        $this->assertEquals('png', $createdProduct->getMedia()->getAt(self::POS_COVER_IMAGE)->getMedia()->getFileExtension());

        $this->assertEquals('51x51', $createdProduct->getMedia()->getAt(1)->getMedia()->getFileName());
        $this->assertEquals('png', $createdProduct->getMedia()->getAt(1)->getMedia()->getFileExtension());
    }

    /**
     * Das erste Bild wird als Cover-Image gesetzt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_3
     *
     * @throws JsonException
     */
    public function addCoverImage(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png'
            ]
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getCoverImage($createdProduct);
        $this->assertEquals('50x50',$coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
    }

    /**
     * Das Cover-Bild ist nicht das erste Bild
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_4
     *
     * @throws JsonException
     */
    public function addCoverImageInBetween(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true
            ]
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getCoverImage($createdProduct);
        $this->assertEquals('51x51', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
    }

    /**
     * Es wird simuliert dass im zweiten Durchlauf das Cover-Bild die Position wechselt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_5
     *
     * @throws JsonException
     */
    public function swapCoverImage(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true
            ]
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getCoverImage($createdProduct);
        $this->assertEquals('51x51', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
        $this->assertEquals(2, $createdProduct->getMedia()->count());

        // Nun wird die Position des Covers gewechselt
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
            ]
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getCoverImage($createdProduct);
        $this->assertEquals('50x50', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
        // Wichtig: Es sind nicht mehr als zwei Bilder dem Produkt zugeordnet
        $this->assertEquals(2, $createdProduct->getMedia()->count());
    }

    /**
     * Es wird im Verlauf ein bestehendes Bild durch ein anderes ersetzt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_6
     *
     * @throws JsonException
     */
    public function replaceImage(): void
    {
        $productDefinition =  $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true
            ]
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getCoverImage($createdProduct);
        $this->assertEquals('51x51', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
        $this->assertEquals(2, $createdProduct->getMedia()->count());

        // Nun wird die Position des Covers gewechselt
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true
            ],
            [
                'url'     => 'https://via.placeholder.com/52x52.png',
            ]
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getCoverImage($createdProduct);
        $this->assertEquals('50x50', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
        // Wichtig: Es sind nicht mehr als zwei Bilder dem Produkt zugeordnet
        $this->assertEquals(2, $createdProduct->getMedia()->count());
    }

    /**
     * Das erste Bild ist IMMER das Cover-Bild. Diese Annahme ist nur bei uns im System so, da das Cover-Bild
     * "intuitiv" immer das erste Bild sein sollte.
     *
     * Technisch gesehen muss das jedoch nicht der Fall sein. Wir implementieren hier unsere eigene Best-Practice
     *
     * @param ProductEntity $productEntity
     *
     * @return ProductMediaEntity
     */
    protected function getCoverImage(ProductEntity $productEntity) : ProductMediaEntity
    {
        foreach ($productEntity->getMedia() as $productMediaEntity) {
            if ($productMediaEntity->getPosition() == 1) {
                return $productMediaEntity;
            }
        }
    }
}
