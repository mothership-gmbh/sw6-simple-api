<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media;


use MothershipSimpleApi\Service\Domain\Media\Validator\ImageValidator;

class MediaRequest
{
    protected array $request = [];

    protected Media $media;

    public function __construct(
        protected ImageValidator $imageValidator,
    ) {
    }

    public function init(array $request): void
    {
        $this->request = $request;
        $media = Media::initWithData($request);
        $this->validate($media);
        $this->media = $media;
    }

    /**
     * Die Definition wird immer vorab durch eine Reihe von Validatoren geprüft, um die
     * Konsistenz der Struktur zu prüfen.
     *
     * @param Media $media
     *
     * @return void
     * @throws Validator\Exception\Image\MissingMediaFolderNameException
     * @throws Validator\Exception\Image\MissingUrlException
     */
    protected function validate(Media $media): void
    {
        $registeredValidator = [
            $this->imageValidator,
        ];
        foreach ($registeredValidator as $validator) {
            $validator->validate($media);
        }
    }

    public function getMedia(): Media
    {
        return $this->media;
    }
}
