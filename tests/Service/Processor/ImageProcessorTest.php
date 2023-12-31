<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Exception\ProductNotFoundException;
use MothershipSimpleApi\Service\Exception\PropertyGroupOptionNotFoundException;
use MothershipSimpleApi\Service\SimpleProductCreator;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;

class ImageProcessorTest extends AbstractProcessorTest
{
    public const POS_COVER_IMAGE = 0;

    protected SimpleProductCreator $simpleProductCreator;

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
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function singleImage(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true,
            ],
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
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function multipleImages(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url' => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url' => 'https://via.placeholder.com/51x51.png',
            ],
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
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function addCoverImage(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true,
            ],
            [
                'url' => 'https://via.placeholder.com/51x51.png',
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getCoverImage($createdProduct);
        $this->assertEquals('50x50', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
    }

    /**
     * Das erste Bild ist IMMER das Cover-Bild. Diese Annahme ist nur bei uns im System so, da das Cover-Bild
     * "intuitiv" immer das erste Bild sein sollte.
     *
     * Technisch gesehen muss das jedoch nicht der Fall sein. Wir implementieren hier unsere eigene Best-Practice
     *
     * @param ProductEntity $productEntity
     *
     * @return ?ProductMediaEntity
     */
    protected function getCoverImage(ProductEntity $productEntity): ?ProductMediaEntity
    {
        foreach ($productEntity->getMedia() as $productMediaEntity) {
            if ($productMediaEntity->getPosition() === 1) {
                return $productMediaEntity;
            }
        }

        return null;
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
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function addCoverImageInBetween(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url' => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true,
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getMediaByFileName($createdProduct, '51x51');
        $this->assertEquals('51x51', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
    }

    /**
     * Es wird simuliert, dass im zweiten Durchlauf das Cover-Bild die Position wechselt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_5
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function swapCoverImage(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url' => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true,
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getMediaByFileName($createdProduct, '51x51');
        $this->assertEquals('51x51', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
        $this->assertEquals(2, $createdProduct->getMedia()->count());

        // Nun wird die Position des Covers gewechselt
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true,
            ],
            [
                'url' => 'https://via.placeholder.com/51x51.png',
            ],
        ];
        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getMediaByFileName($createdProduct, '50x50');
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
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function replaceImage(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url' => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true,
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $coverImage = $this->getMediaByFileName($createdProduct, '51x51');
        $this->assertEquals('51x51', $coverImage->getMedia()->getFileName());
        $this->assertEquals($createdProduct->getCoverId(), $coverImage->getId());
        $this->assertEquals(2, $createdProduct->getMedia()->count());

        // Nun wird die Position des Covers gewechselt
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true,
            ],
            [
                'url' => 'https://via.placeholder.com/52x52.png',
            ],
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
     * Die Reihenfolge der importierten Produkte kann sich auch ändern.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_7
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function changeImageOrder()
    {

        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url' => 'https://via.placeholder.com/50x50.png',
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true,
            ],
            [
                'url'     => 'https://via.placeholder.com/52x52.png',
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        self::assertEquals(1, $this->getMediaByFileName($createdProduct, '50x50')->getPosition());
        self::assertEquals(2, $this->getMediaByFileName($createdProduct, '51x51')->getPosition());
        self::assertEquals(3, $this->getMediaByFileName($createdProduct, '52x52')->getPosition());


        // Ändere die Reihenfolge.
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true,
            ],
            [
                'url' => 'https://via.placeholder.com/50x50.png',
            ],

            [
                'url'     => 'https://via.placeholder.com/52x52.png',
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        self::assertEquals(2, $this->getMediaByFileName($createdProduct, '50x50')->getPosition());
        // Hier hat sich die Reihenfolge der Bilder geändert.
        self::assertEquals(1, $this->getMediaByFileName($createdProduct, '51x51')->getPosition());
        self::assertEquals(3, $this->getMediaByFileName($createdProduct, '52x52')->getPosition());
    }

    /**
     * Die Simple-API bietet die Möglichkeit einen Dateinamen für das Bild explizit zu übergeben.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Processor
     * @group SimpleApi_Product_Processor_Image
     * @group SimpleApi_Product_Processor_Image_8
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     * @throws ProductNotFoundException
     * @throws PropertyGroupOptionNotFoundException
     */
    public function imageWithCustomFileName(): void
    {
        $productDefinition = $this->getMinimalDefinition();
        $productDefinition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'file_name' => 'test_name.png',
                'isCover' => true
            ],
        ];

        $this->simpleProductCreator->createEntity($productDefinition, $this->getContext());
        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertEquals('test_name', $createdProduct->getMedia()->getAt(self::POS_COVER_IMAGE)->getMedia()->getFileName());
        $this->assertEquals('png', $createdProduct->getMedia()->getAt(self::POS_COVER_IMAGE)->getMedia()->getFileExtension());
        // Es gibt auch nur ein Bild
        $this->assertEquals(1, $createdProduct->getMedia()->count());
    }



    protected function getMediaByFileName(ProductEntity $productEntity, string $filename)
    {
        foreach ($productEntity->getMedia() as $media) {
            if ($media->getMedia()->getFileName() === $filename) {
                return $media;
            }
        }
    }
}
