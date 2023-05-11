<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Validator;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Validator\Exception\SalesChannel\InvalidSalesChannelVisibilityException;

class SalesChannelValidator implements IValidator
{
    /**
     * @throws InvalidSalesChannelVisibilityException
     */
    public function validate(Product $product): void
    {
        // ProductVisibilityDefinition::VISIBILITY_ALL
        $validVisibilities = [
            'all',
            'link',
            'search',
        ];
        $salesChannels = $product->getSalesChannel();


        if (null !== $salesChannels) {
            foreach ($salesChannels as $salesChannel => $visibility) {
                if (!in_array($visibility, $validVisibilities, true)) {
                    throw new InvalidSalesChannelVisibilityException('The visibility [' . $visibility . '] for [' . $salesChannel . '] is invalid.');
                }
            }
        }
    }
}
