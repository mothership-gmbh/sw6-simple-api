<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor\Promotion;

use BadMethodCallException;
use MothershipSimpleApi\Service\Definition\PromotionCode;
use MothershipSimpleApi\Service\Validator\MissingValueException;
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

    /**
     * @throws MissingValueException
     */
    public function process(PromotionCode $promotionCode, array $requestData, Context $context): void
    {
        $this->validate($requestData);
        $promotion = $this->searchPromotion($requestData, $context);
        if ($promotion === null) {
            $this->createPromotion($requestData, $context);
            $promotion = $this->searchPromotion($requestData, $context);
        }

        $this->mapPromotionToPromotionCode($promotion, $promotionCode);
    }

    private function getFilterForField(string $field, string $type, ?string $association, mixed $value): Filter
    {
        $field = $association === null ? $field : $association . '.' . $field;
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
                $filter = $this->getFilterForField($fieldOptions['field'], $fieldOptions['type'], $fieldOptions['association'], $value);
                $criteria->addFilter($filter);
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
        $promotionData['name'] = $requestData['name'] ?? "$value € Promotion";
        $promotionData['individualCodePattern'] = $requestData['individual_code_pattern'] ?? $this->createCodePatternByRequest($requestData);
        $promotionData['salesChannels'] = array_map(static fn($id) => ['salesChannelId' => $id, 'priority' => 1], $salesChannelIds);

        $this->promotionRepository->upsert([$promotionData], $context);
    }

    /**
     * Pattern has to be unique per promotion object! Shopware won't allow shared code patterns. This method tries
     * to generate a unique hash from request data via crc32 and converts it to an alphanumeric suffix added to a
     * pattern.
     *
     * @param array $requestData
     * @return string
     */
    private function createCodePatternByRequest(array $requestData): string
    {
        $suffix = strtoupper(base_convert(hash('crc32b', serialize($requestData)), 16, 36));
        // crc32 max value is X'ffffffff', therefore base_convert max value will be '1z141z3'. Since the first character
        // is always '1' when having 7 characters, let's remove it so coupon code is a bit smaller.
        if (strlen($suffix) > 6) {
            $suffix = substr($suffix, -6);
        }

        return "CB%s%s%s%s{$suffix}";
    }

    /**
     * @param PromotionEntity $promotion
     * @param PromotionCode $promotionCode
     * @return void
     */
    private function mapPromotionToPromotionCode(PromotionEntity $promotion, PromotionCode $promotionCode): void
    {
        $promotionCode->assign($promotion->getVars());
        $promotionCode->assign($promotion->getDiscounts()?->first()?->getVars() ?? [], 'discounts');
        $promotionCode->set('code_blacklist', $promotion->getIndividualCodes()?->getCodeArray() ?? []);
        $promotionCode->set('sales_channel', $promotion->getSalesChannels()?->getIds());
    }

    /**
     * @param array $requestData
     * @return void
     * @throws MissingValueException
     */
    private function validate(array $requestData): void
    {
        if (!isset($requestData['value'])) {
            throw new MissingValueException('Required parameter missing: value');
        }
    }
}