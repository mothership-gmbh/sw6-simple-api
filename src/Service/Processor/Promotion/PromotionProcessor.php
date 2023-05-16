<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor\Promotion;

use BadMethodCallException;
use MothershipSimpleApi\Service\Definition\PromotionCode;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class PromotionProcessor implements PromotionProcessorInterface
{
    public function __construct(
        protected EntityRepository $promotionRepository,
        protected EntityRepository $salesChannelRepository,
    )
    {
    }

    public function process(PromotionCode $promotionCode, array $requestData, Context $context): void
    {
        $promotion = $this->searchPromotion($requestData, $context);
        if ($promotion === null) {
            $this->createPromotion($requestData, $context);
            $promotion = $this->searchPromotion($requestData, $context);
        }

        $this->mapPromotionToPromotionCode($promotion, $promotionCode);
    }

    private function getFilterForField(string $field, string $type, mixed $value): Filter
    {
        return match ($type) {
            'bool'     => new EqualsFilter($field, (bool)$value),
            'datetime' => new PrefixFilter($field, (string)$value),
            'float'    => new EqualsFilter($field, (float)$value),
            'int'      => new EqualsFilter($field, (int)$value),
            'array'    => throw new BadMethodCallException('array type is not supported'),
            default    => new EqualsFilter($field, (string)$value),
        };
    }

    /**
     * @param array $requestData
     * @param Context $context
     * @return ?PromotionEntity
     */
    private function searchPromotion(array $requestData, Context $context): ?PromotionEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('individualCodes');
        $criteria->addAssociation('discounts');
        foreach ($requestData as $property => $value) {
            if (isset(PromotionCode::PROPERTY_DEFINITION[$property])) {
                $fieldOptions = PromotionCode::PROPERTY_DEFINITION[$property];
                $filter = $this->getFilterForField($fieldOptions['field'], $fieldOptions['type'], $value);
                if (isset($fieldOptions['association'])) {
                    $criteria->getAssociation($fieldOptions['association'])->addFilter($filter);
                } else {
                    $criteria->addFilter($filter);
                }
            }
        }

        /** @var ?PromotionEntity $promotion */
        $promotion = $this->promotionRepository->search($criteria, $context)->first();
        return $promotion;
    }

    private function createPromotion(array $requestData, Context $context): void
    {
        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), $context)->getIds();

        $promotionData = [];
        foreach (PromotionCode::PROPERTY_DEFINITION as $field => $fieldDefinition) {
            $value = $requestData[$field] ?? $fieldDefinition['default'] ?? null;
            if ($value === null) {
                continue;
            }
            if (isset($fieldDefinition['association'])) {
                $association = $fieldDefinition['association'];
                if (!isset($promotionData[$association])) {
                    $promotionData[$association] = [[]];
                }
                $promotionData[$association][0][$fieldDefinition['field']] = $value;
            } else {
                $promotionData[$fieldDefinition['field']] = $value;
            }
        }

        // Set fixed values
        $value = (int)$requestData['value'];
        $promotionData['id'] = Uuid::randomHex();
        $promotionData['name'] = "$value € Promotion";
        $promotionData['individualCodePattern'] = $this->createCodePatternByRequest($requestData);
        $promotionData['salesChannels'] = array_map(static fn ($id) => ['salesChannelId' => $id, 'priority' => 1], $salesChannelIds);

        $this->promotionRepository->upsert([$promotionData], $context);
    }

    /**
     * Pattern has to be unique per promotion object! Shopware won't allow shared code patterns.
     *
     * @param array $requestData
     * @return string
     */
    private function createCodePatternByRequest(array $requestData): string
    {
        $suffix = '';
        if (isset($requestData['valid_until'])) {
            // Create a short unique suffix based on the days from 2000 until 'valid_until' and convert it to base 36
            $daysSinceDawnOfTime = round((strtotime($requestData['valid_until']) - strtotime('2000-01-01')) / 60 / 60 / 24);
            $suffix .= strtoupper(base_convert((string)$daysSinceDawnOfTime, 10, 36));
        }

        $value = (int)$requestData['value'];
        return "CB{$value}%s%s%s%s{$suffix}";
    }

    /**
     * @param PromotionEntity $promotion
     * @param PromotionCode $promotionCode
     * @return void
     */
    private function mapPromotionToPromotionCode(PromotionEntity $promotion, PromotionCode $promotionCode): void
    {
        $promotionCode->assign($promotion->getVars());
        $promotionCode->assign($promotion->getDiscounts()?->first()?->getVars(), 'discounts');
        $promotionCode->set('code_blacklist', $promotion->getIndividualCodes()?->getCodeArray() ?? []);
        $promotionCode->set('sales_channel', $promotion->getSalesChannels()?->getIds());
    }
}
