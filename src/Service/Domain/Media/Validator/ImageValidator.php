<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media\Validator;

use MothershipSimpleApi\Service\Domain\Media\Media;
use MothershipSimpleApi\Service\Domain\Media\Validator\Exception\Image\MissingMediaFolderNameException;
use MothershipSimpleApi\Service\Domain\Media\Validator\Exception\Image\MissingUrlException;

class ImageValidator implements IValidator
{

    /**
     * @param Media $media
     *
     * @return void
     * @throws MissingMediaFolderNameException
     * @throws MissingUrlException
     */
    public function validate(Media $media): void
    {
        $url = $media->getUrl();
        if (null === $url) {
            throw new MissingUrlException();
        }

        $mediaFolder = $media->getMediaFolderName();
        if (null === $mediaFolder) {
            throw new MissingMediaFolderNameException();
        }
    }
}
