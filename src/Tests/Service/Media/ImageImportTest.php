<?php

namespace MothershipSimpleApi\Tests\Service\Media;

use JsonException;
use MothershipSimpleApi\Service\Media\ImageImport;
use MothershipSimpleApi\Service\SimpleProductCreator;
use MothershipSimpleApi\Tests\Service\AbstractTestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ImageImportTest extends AbstractTestCase
{
    protected ImageImport $imageImport;
    protected MediaService $mediaService;
    protected EntityRepositoryInterface $mediaRepository;

    protected function setUp(): void
    {
        $this->imageImport = $this->getContainer()->get(ImageImport::class);
        $this->mediaService = $this->getContainer()->get(\Shopware\Core\Content\Media\MediaService::class);
     //   $this->deleteProductBySku($product['sku']);
        $this->mediaRepository = $this->getContainer()->get('media.repository');
        $this->mediaRepository->delete([['id' => '19DE66FAB5C7C174BF4C3F0A08FAD527']], $this->getContext());
    }

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
     * @throws JsonException
     */
    public function addSingleImage(): void
    {
        $url = __DIR__ . '/fixtures/team_wanderung.jpeg';

        $mediaId = $this->imageImport->addImageToMediaFromResource($url, $this->getContext());

        $this->assertHashEquals($url, $mediaId);
        $this->assertFileEuals($url, $mediaId);
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
     * @throws JsonException
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
     * @param string $resource
     * @param string $mediaId
     *
     * @return void
     */
    private function assertHashEquals(string $resource, string $mediaId)
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($mediaId)]);
        $media = $this->mediaRepository->search($criteria, $this->getContext())->first();

        $metaData = $media->getMetaData();
        $hash = hash_file('sha1', $resource);
        $this->assertEquals($hash, $metaData['hash']);
    }

    private function assertFileEuals(string $resource, string $mediaId)
    {
        $this->assertEquals(file_get_contents($resource), $this->mediaService->loadFile($mediaId, $this->getContext()));
    }
}
