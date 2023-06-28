<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use MothershipSimpleApi\Service\Media\ImageImport;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ImageProcessor
{

    protected ImageImport $imageImport;

    protected EntityRepository $productMediaRepository;

    protected ?string $coverImageId = null;
    protected ?string $coverImageMediaId = null;

    // https://stackoverflow.com/questions/63410548/media-creation-via-php-in-shopware-6
    // https://shopwarian.com/how-to-add-images-to-products-programmatically-in-shopware-6/

    public function __construct(
        ImageImport      $imageImport,
        EntityRepository $productMediaRepository
    )
    {
        $this->imageImport = $imageImport;
        $this->productMediaRepository = $productMediaRepository;
    }

    public function process(Product $request, string $productUuid, Context $context): array
    {
        $mediaUuids = [];
        $images = [];
        if (empty($request->getImages())) {
            return [];
        }
        foreach ($request->getImages() as $image) {
            $url = $image['url'];
            $fileName = $image['file_name'] ?? null;

            $mediaUuid = $this->imageImport->addImageToMediaFromResource($url, $context, $fileName);

            /*
             * Das erste Bild soll immer das Cover-Bild sein. Dies kann nachträglich überschrieben
             * werden, wenn das Flag 'isCover' gesetzt wird.
             *
             * So soll sichergestellt werden, dass jedes Produkt ein Cover-Bild besitzt.
             */
            if (null === $this->coverImageId) {
                $this->coverImageId = $mediaUuid;
                $this->coverImageMediaId = BitwiseOperations::xorHex($productUuid, $mediaUuid);
            }
            if (array_key_exists('isCover', $image)) {
                $this->coverImageId = $mediaUuid;
                $this->coverImageMediaId = BitwiseOperations::xorHex($productUuid, $mediaUuid);
            }

            /*
             * Es muss immer sichergestellt werden, dass die UUID der Verknüpfung product-media eindeutig und
             * reproduzierbar ist.
             *
             * Um das zu gewährleisten, werden die beiden UUIDs des Mediums und des Produktes kombiniert.
             */
            $productMediaUuid = BitwiseOperations::xorHex($productUuid, $mediaUuid);

            // Wird gesammelt, um darauf basierend nachher ein Diff durchzuführen.
            $mediaUuids[] = $productMediaUuid;
            $images[$mediaUuid] = [
                'id'        => $productMediaUuid,
                'productId' => $productUuid,
                'mediaId'   => $mediaUuid,
            ];
        }

        // Das Cover wird explizit über den Eintrag cover-ID gesetzt und muss daher aus den array entfernt werden.
        // unset($images[$this->coverImageId]);

        /* Prüft, ob es Änderungen gibt. Falls ja, werden alle Zuordnungen entfernt
         */
        $this->removeEntriesIfRequired($productUuid, $mediaUuids, $context);

        return [
            'id'    => $productUuid,
            'cover' => [
                'id'       => $this->coverImageMediaId,
                'mediaId'  => $this->coverImageId,
                 // 'position' => 1,
            ],
            'media' => $this->applyPositions($images),
        ];
    }

    protected function removeEntriesIfRequired(string $productUuid, array $mediaUuids, Context $context): void
    {
        $requiresCleanup = false;
        $assignedImages = $this->getAllAssignedMediaByProductId($productUuid, $context);
        if (!empty($assignedImages)) {
            if (count($mediaUuids) !== count($assignedImages)) {
                $requiresCleanup = true;
            }
            if (count(array_diff($mediaUuids, $assignedImages)) > 0) {
                $requiresCleanup = true;
            }
            if (count(array_diff($assignedImages, $mediaUuids)) > 0) {
                $requiresCleanup = true;
            }
        }

        if ($requiresCleanup) {
            foreach ($assignedImages as $assignedImage) {
                $this->productMediaRepository->delete([['id' => $assignedImage]], $context);
            }
        }
    }

    private function getAllAssignedMediaByProductId(string $productUuid, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productUuid));
        return $this->productMediaRepository->search($criteria, $context)->getIds();
    }

    protected function applyPositions(array $images): array
    {
        $i = 1;
        foreach ($images as $mediaUuid => $image) {
            $images[$mediaUuid]['position'] = $i;
            $i++;
        }
        return $images;
    }
}
