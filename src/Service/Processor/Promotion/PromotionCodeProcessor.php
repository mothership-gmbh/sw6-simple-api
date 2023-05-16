<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor\Promotion;

use MothershipSimpleApi\Service\Definition\PromotionCode;
use Shopware\Core\Checkout\Promotion\Util\PromotionCodeService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class PromotionCodeProcessor implements PromotionProcessorInterface
{
    public function __construct(
        protected EntityRepository     $individualCodesRepository,
        protected PromotionCodeService $promotionCodeService,
    )
    {
    }

    public function process(PromotionCode $promotionCode, array $requestData, Context $context): void
    {
        $codes = $this->promotionCodeService->generateIndividualCodes(
            $promotionCode->get('individual_code_pattern'),
            1,
            $promotionCode->get('code_blacklist')
        );
        $promotionCode->set('code', current($codes));

        $this->individualCodesRepository->upsert([['promotionId' => $promotionCode->get('promotion_id'), 'code' => current($codes)]], $context);
    }
}
