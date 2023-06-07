<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\InvalidIsoCodeException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingTypeDefinitionException;
use MothershipSimpleApi\Service\Validator\Exception\CustomField\MissingValuesException;
use MothershipSimpleApi\Service\Validator\Exception\Trait\InvalidCodeFormatException;
use MothershipSimpleApi\Service\Validator\Trait\CodeTrait;

class CustomFieldValidator implements IValidator
{

    use CodeTrait;

    /**
     * @throws InvalidIsoCodeException
     * @throws MissingValuesException
     * @throws MissingTypeDefinitionException
     * @throws InvalidDefinitionException
     * @throws InvalidCodeFormatException
     */
    public function validate(Product $product): void
    {
        $customFields = $product->getCustomFields();
        foreach ($customFields as $code => $values) {

            if (!is_array($values)) {
                throw new InvalidDefinitionException('An array must be provided as argument for custom fields');
            }

            $this->hasValidFormat($code);

            $this->hasValidStructure($values);

            foreach ($values['values'] as $isoCode => $value) {
                $this->isoCodeIsValid($isoCode);
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
    private function isoCodeIsValid(string $isoCode): void
    {
        if (!preg_match("/[a-z]{2}-[A-Z]{2}/", $isoCode)) {
            throw new InvalidIsoCodeException("The provided Iso-Code [$isoCode] does not match the schema aa-AA.");
        }
    }
}
