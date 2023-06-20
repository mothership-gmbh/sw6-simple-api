<?php

namespace MothershipSimpleApiTests\Service\Media;

use MothershipSimpleApi\Service\Media\ImageImport;
use MothershipSimpleApiTests\Service\AbstractTestCase;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;

class ImageImportTest extends AbstractTestCase
{
    protected ImageImport $imageImport;
    protected MediaService $mediaService;
    protected EntityRepositoryInterface $mediaRepository;

    /**
     * Fügt dem Produkt ein einzelnes Bild hinzu
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Service
     * @group SimpleApi_Product_Service_Media
     * @group SimpleApi_Product_Service_Media_ImageImport
     * @group SimpleApi_Product_Service_Media_ImageImport_1
     */
    public function addSingleImage(): void
    {
        $url = __DIR__ . '/fixtures/team_wanderung.jpeg';

        $mediaId = $this->imageImport->addImageToMediaFromResource($url, $this->getContext());

        $this->assertHashEquals($url, $mediaId);
        $this->assertFileEuals($url, $mediaId);
    }

    /**
     * @param string $resource
     * @param string $mediaId
     *
     * @return void
     */
    private function assertHashEquals(string $resource, string $mediaId): void
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($mediaId)]);
        $media = $this->mediaRepository->search($criteria, $this->getContext())->first();

        $metaData = $media->getMetaData();
        $hash = hash_file('sha1', $resource);
        $this->assertEquals($hash, $metaData['hash']);
    }

    private function assertFileEuals(string $resource, string $mediaId): void
    {
        $this->assertStringEqualsFile($resource, $this->mediaService->loadFile($mediaId, $this->getContext()));
    }

    /**
     * Es wird ein Bild hochgeladen, welches den Namen "team_wanderung" hat. Danach wird ein neues Bild
     * mit dem gleichen Namen hochgeladen. Sprich: Der Hash ändert sich und somit das hochzuladende Bild.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Service
     * @group SimpleApi_Product_Service_Media
     * @group SimpleApi_Product_Service_Media_ImageImport
     * @group SimpleApi_Product_Service_Media_ImageImport_2
     */
    public function overWriteImage(): void
    {
        $url = __DIR__ . '/fixtures/team_wanderung.jpeg';

        $mediaId = $this->imageImport->addImageToMediaFromResource($url, $this->getContext());
        $this->assertHashEquals($url, $mediaId);
        $this->assertFileEuals($url, $mediaId);

        // Im nächsten Schritt wird das bestehende Bild überschrieben.
        $url = __DIR__ . '/fixtures/overwrite/team_wanderung.jpeg';

        $mediaId = $this->imageImport->addImageToMediaFromResource($url, $this->getContext());
        $this->assertHashEquals($url, $mediaId);
        $this->assertFileEuals($url, $mediaId);
    }

    /**
     * Ein Bild wird einem bestimmten Ordner zugeordnet.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Service
     * @group SimpleApi_Product_Service_Media
     * @group SimpleApi_Product_Service_Media_ImageImport
     * @group SimpleApi_Product_Service_Media_ImageImport_3
     */
    public function uploadAndAssignImageToSpecificMediaFolderId()
    {
        $url = __DIR__ . '/fixtures/team_wanderung.jpeg';

        $mediaId = $this->imageImport->addImageToMediaFromResource($url, $this->getContext(), 'Category Media');
        $this->assertHashEquals($url, $mediaId);
        $this->assertFileEuals($url, $mediaId);

        // Das Bild ist nun dem Ordner "Category Media" zugeordnet.
        $criteria = new Criteria();
        $criteria->setIds([strtolower($mediaId)]);
        $criteria->addAssociation('mediaFolder');
        $media = $this->mediaRepository->search($criteria, $this->getContext())->first();
        self::assertEquals('Category Media', $media->getMediaFolder()->getName());
    }

    protected function setUp(): void
    {
        $this->imageImport = $this->getContainer()->get(ImageImport::class);
        $this->mediaService = $this->getContainer()->get(MediaService::class);
        //   $this->deleteProductBySku($product['sku']);
        $this->mediaRepository = $this->getContainer()->get('media.repository');
        $this->mediaRepository->delete([['id' => '19DE66FAB5C7C174BF4C3F0A08FAD527']], $this->getContext());
    }
}
