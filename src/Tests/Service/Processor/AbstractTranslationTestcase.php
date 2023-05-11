<?php

namespace MothershipSimpleApi\Tests\Service\Processor;

use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\ProductEntity;

abstract class AbstractTranslationTestcase extends AbstractProcessorTest
{
    protected function getTranslationElementByIsoCode(ProductEntity $createdProduct, string $isoCode) : ProductTranslationEntity
    {
        $productId  = $createdProduct->getUniqueIdentifier();
        $languageId = $this->getTranslationIdByIsoCode($isoCode);

        // Die Translation-Id wird über einen kombinierten Schlüssel erstellt
        $translationId = $productId . "-" . $languageId;
        return $createdProduct->getTranslations()->getElements()[$translationId];
    }
}
