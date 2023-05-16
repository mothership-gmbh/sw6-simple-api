<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor\Promotion;

use MothershipSimpleApi\Service\Definition\PromotionCode;
use Shopware\Core\Framework\Context;

interface PromotionProcessorInterface
{
    public function process(PromotionCode $promotionCode, array $requestData, Context $context): void;
}
