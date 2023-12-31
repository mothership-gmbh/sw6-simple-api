<?php

namespace MothershipSimpleApi\Service\Validator\Trait;

use MothershipSimpleApi\Service\Validator\Exception\Trait\InvalidCodeFormatException;

trait CodeTrait
{
    /**
     * Der Nutzer der SimpleApi ist dafür verantwortlich, dass er den
     * CustomField-/PropertyGroup- oder PropertyGroupOption-Code in einem validen Format übergibt.
     *
     * Deutsche Umlaute müssen vorhanden sein, weil in einigen Projekten die custom-fields diese enthalten.
     * Es ist nicht optimal, aber historisch gewachsen, weswegen der Support notwendig ist.
     *
     * @throws InvalidCodeFormatException
     */
    protected function hasValidFormat(string $code): void
    {
        // Ist Code nicht in camelCase oder snake_case?
        if (!preg_match('/^([a-zäöüß0-9]+)([A-ZÄÖÜß]?[a-zäöüß0-9]+)+$/u', $code) && !preg_match('/^[a-zäöüß0-9]+(_[a-zäöüß0-9]+)*$/', $code)) {
            throw new InvalidCodeFormatException("The format of the provided code [$code] is invalid. Use either camelCase or snake_case. The label or display name can be manually changed later on.");
        }
    }
}
