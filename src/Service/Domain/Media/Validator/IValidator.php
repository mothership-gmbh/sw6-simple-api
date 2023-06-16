<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media\Validator;

use MothershipSimpleApi\Service\Domain\Media\Media;

interface IValidator
{
    public function validate(Media $media): void;
}
