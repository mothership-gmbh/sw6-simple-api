<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingTypeDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingValuesException;

class CustomFieldValidator implements IValidator
{
    /**
     * @throws InvalidIsoCodeException
     * @throws MissingValuesException
     * @throws MissingTypeDefinitionException
     * @throws InvalidDefinitionException
     */
    public function validate(Product $product): void
    {
        $customFields = $product->getCustomFields();
        foreach ($customFields as $values) {

            if (!is_array($values)) {
                throw new InvalidDefinitionException('An array must be provided as argument for custom fields');
            }

            $this->hasValidStructure($values);

            foreach ($values['values'] as $isoCode => $value) {
                $this->isCodeIsValid($isoCode);
            }
        }
    }

    /**
     * @throws MissingValuesException
     * @throws MissingTypeDefinitionException
     */
    private function hasValidStructure(array $values): void
    {
        if (!array_key_exists('type', $values)) {
            throw new MissingTypeDefinitionException('The [type] definition is missing');
        }

        if (!array_key_exists('values', $values)) {
            throw new MissingValuesException('The [values] definition is missing');
        }
    }

    /**
     * @throws InvalidIsoCodeException
     */
    private function isCodeIsValid(string $isoCode): void
    {
        if (!preg_match("/[a-z]{2}-[A-Z]{2}/", $isoCode)) {
            throw new InvalidIsoCodeException('The provided Iso-Code ' . $isoCode . ' does not match the schema aa-AA.');
        }
    }
}
