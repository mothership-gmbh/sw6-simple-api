<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media\Validator\Exception\Image;

class MissingMediaFolderNameException extends \Exception
{
    protected $message = 'The media folder name is missing';

    public function __construct(string $message = null)
    {
        parent::__construct($message ?? $this->message);
    }
}