<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\SalesChannel\InvalidSalesChannelVisibilityException;

class SalesChannelValidator implements IValidator
{
    public function validate(Product $product): void
    {
        // ProductVisibilityDefinition::VISIBILITY_ALL
        $validVisibilities = [
            'all',
            'link',
            'search',
        ];
        $salesChannel = $product->getSalesChannel();


        if (null !== $salesChannel) {
            foreach ($salesChannel as $salesChannel => $visibility) {
                if (!in_array($visibility, $validVisibilities)) {
                    throw new InvalidSalesChannelVisibilityException('The visibility [' . $visibility . '] for [' . $salesChannel . '] is invalid.');
                }
            }
        }
    }
}
