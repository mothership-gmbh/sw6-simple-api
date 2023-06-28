<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\Image\DuplicatedCoverAssignmentException;
use MothershipSimpleApi\Service\Validator\Exception\Image\DuplicatedUrlException;
use MothershipSimpleApi\Service\Validator\Exception\Image\InvalidDataTypeException;
use MothershipSimpleApi\Service\Validator\Exception\Image\InvalidFileExtensionException;
use MothershipSimpleApi\Service\Validator\Exception\Image\MissingUrlKeyException;

class ImageValidator implements IValidator
{
    /**
     * @throws MissingUrlKeyException
     * @throws DuplicatedUrlException
     * @throws DuplicatedCoverAssignmentException
     * @throws InvalidDataTypeException
     */
    public function validate(Product $product): void
    {
        $images = $product->getImages();
        if (null !== $images) {

            $urls = [];
            $coverImageSet = false;

            foreach ($images as $image) {
                if (is_string($image)) {
                    throw new InvalidDataTypeException('The image property is not an array');
                }
                if (!array_key_exists('url', $image)) {
                    throw new MissingUrlKeyException('The image property is missing the [url] parameter');
                }
                if (in_array($image['url'], $urls, true)) {
                    throw new DuplicatedUrlException('The image [' . $image['url'] . '] is duplicated');
                }

                if (array_key_exists('isCover', $image)) {
                    if ($coverImageSet) {
                        throw new DuplicatedCoverAssignmentException('The image [' . $image['url'] . '] can not be the cover image. Cover image already set.');
                    }
                    $coverImageSet = true;
                }
                $urls[] = $image['url'];

                $this->customFileNameIsValid($image);
            }
        }
    }

    protected function customFileNameIsValid(array $image): void
    {
        if (array_key_exists('file_name', $image)) {
            if (!preg_match('/\.(jpe?g|png|gif|tiff|bmp|webp)$/i', $image['file_name'])) {
                throw new InvalidFileExtensionException('The file name [' . $image['file_name'] . '] has no valid extension. Use jpg, jpeg, png, gif, tiff, bmp or webp.');
            }
        }
    }
}
