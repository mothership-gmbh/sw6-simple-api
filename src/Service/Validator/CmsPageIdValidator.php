<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\InvalidUuidException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\MissingIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\MissingNameException;
use Shopware\Core\Framework\Uuid\Uuid;

class CmsPageIdValidator implements IValidator
{
    public function validate(Product $product): void
    {
        $pageId = $product->getCmsPageId();

        if (null !== $pageId && !Uuid::isValid($pageId)) {
            throw new InvalidUuidException('The provided cms page id is not valid.');
        }
    }
}
