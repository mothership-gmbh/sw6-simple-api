<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media\Validator\Exception\Image;

class MissingUrlException extends \Exception
{
    protected $message = 'The image url is missing';

    public function __construct(string $message = null)
    {
        parent::__construct($message ?? $this->message);
    }
}