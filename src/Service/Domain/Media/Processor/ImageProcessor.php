<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media\Processor;

use MothershipSimpleApi\Service\Domain\Media\Media;
use MothershipSimpleApi\Service\Media\ImageImport;
use Shopware\Core\Framework\Context;

class ImageProcessor
{

    public function __construct(protected ImageImport $imageImport)
    {
    }

    public function process(Media $media, Context $context): string
    {
        return $this->imageImport->addImageToMediaFromResource($media->getUrl(), $context, $media->getMediaFolderName());
    }
}
