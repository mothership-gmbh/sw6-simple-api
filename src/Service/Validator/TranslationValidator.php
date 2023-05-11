<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\Translation\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\MissingIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\Translation\MissingNameException;

class TranslationValidator implements IValidator
{
    public function validate(Product $product): void
    {
        $name = $product->getName();
        if (null === $name) {
            throw new MissingNameException('The mandatory attribute [name] is missing');
        }

        $translatableFields = ['name', 'description', 'keywords', 'meta_title', 'meta_description'];

        foreach ($translatableFields as $translatableField) {
            $translatable = $product->{'get' . ucfirst($translatableField)}();
            if (null !== $translatable) {

                if (!is_array($translatable)) {
                    throw new MissingIsoCodeException('The translateable field [' . $translatableField . '] requires a key-value pair [iso-code => value]');
                }
                foreach ($translatable as $isoCode => $value) {
                    $this->isCodeIsValid($isoCode);
                }
            }
        }
    }

    private function isCodeIsValid(string $isoCode)
    {
        if (!preg_match("/[a-z]{2}-[A-Z]{2}/", $isoCode)) {
            throw new InvalidIsoCodeException('The provided Iso-Code ' . $isoCode . ' does not match the schema aa-AA.');
        }
    }
}
