<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;

class CmsPageIdValidator implements IValidator
{
    /**
     * @throws InvalidUuidException
     */
    public function validate(Product $product): void
    {
        $pageId = $product->getCmsPageId();

        if (null !== $pageId && !Uuid::isValid($pageId)) {
            throw new InvalidUuidException('The provided cms page id is not valid.');
        }
    }
}
