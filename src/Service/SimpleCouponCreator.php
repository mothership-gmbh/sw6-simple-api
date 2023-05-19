<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service;

use MothershipSimpleApi\Service\Definition\PromotionCode;
use MothershipSimpleApi\Service\Processor\Promotion\PromotionCodeProcessor;
use MothershipSimpleApi\Service\Processor\Promotion\PromotionProcessor;
use Shopware\Core\Framework\Context;

class SimpleCouponCreator
{
    public function __construct(
        protected PromotionProcessor     $promotionProcessor,
        protected PromotionCodeProcessor $promotionCodeProcessor,
    )
    {
    }

    public function create(array $data, Context $context): PromotionCode
    {
        // Check if promotion with given request data exists. If not, a new promotion will be created.
        $promotionCode = new PromotionCode();
        $this->promotionProcessor->process($promotionCode, $data, $context);

        // Create coupon and return detailed information
        $this->promotionCodeProcessor->process($promotionCode, $data, $context);

        return $promotionCode;
    }
}
