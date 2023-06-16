<?php

namespace MothershipSimpleApiTests\Service\Domain\Media\Processor;

use MothershipSimpleApi\Service\Domain\Media\MediaCreator;
use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ImageProcessorTest extends AbstractProcessorTest
{
    protected MediaCreator $mediaCreator;

    /**
     * Fügt dem Produkt ein einzelnes Bild hinzu
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Domain
     * @group SimpleApi_Domain_Media
     * @group SimpleApi_Domain_Media_Processor
     * @group SimpleApi_Domain_Media_Processor_Image
     * @group SimpleApi_Domain_Media_Processor_Image_1
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function singleImage(): void
    {
        $productDefinition = $this->getMinimalDefinition();

        $mediaId = $this->mediaCreator->createEntity($productDefinition, $this->getContext());
        $media = $this->getMediaById($mediaId);

        $this->assertEquals('50x50', $media->getFileName());
        $this->assertEquals('png', $media->getFileExtension());
        $this->assertEquals($productDefinition['media_folder_name'], $media->getMediaFolder()->getName());
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

    protected function getMediaById(string $mediaId): MediaEntity|null
    {
        $mediaRepository = $this->getRepository('media.repository');

        $criteria = new Criteria([$mediaId]);
        $criteria->addAssociations(['mediaFolder']);
        $mediaEntity = $mediaRepository->search($criteria, $this->getContext())->first();
        return $mediaEntity ?? null;
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
