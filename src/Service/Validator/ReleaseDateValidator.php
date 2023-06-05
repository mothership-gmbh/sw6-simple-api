<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use DateTime;
use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\ReleaseDate\InvalidDateFormatException;

class ReleaseDateValidator implements IValidator
{
    /**
     * @throws InvalidDateFormatException
     */
    public function validate(Product $product): void
    {
        $releaseDate = $product->getReleaseDate();

        if (null !== $releaseDate && !$this->validateDate($releaseDate)) {
            throw new InvalidDateFormatException('The provided ReleaseDate has no valid date format. Use the format "Y-m-d H:i:s".');
        }
    }

    /**
     * @link https://www.php.net/manual/en/function.checkdate.php
     */
    protected function validateDate($date, $format = 'Y-m-d H:i:s'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
