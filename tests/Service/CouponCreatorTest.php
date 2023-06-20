<?php

namespace MothershipSimpleApiTests\Service;

use MothershipSimpleApi\Service\SimpleCouponCreator;
use MothershipSimpleApi\Service\Validator\MissingValueException;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class CouponCreatorTest extends AbstractTestCase
{
    protected SimpleCouponCreator $simpleCouponCreator;

    protected function setUp(): void
    {
        $this->simpleCouponCreator = $this->getContainer()->get(SimpleCouponCreator::class);
        $this->deletePromotions();
    }

    /**
     * Erstellt eine Promotion und einen Coupon
     */
    public function testBasicCouponCreation(): void
    {
        $promoCode = $this->simpleCouponCreator->create(['value' => 10], $this->getContext());
        $this->assertEquals(10, $promoCode->get('value'));
        $this->assertStringStartsWith('CB', $promoCode->get('code'));

        $promotionEntity = $this->getPromotionById($promoCode->get('promotion_id'));
        $this->assertInstanceOf(PromotionEntity::class, $promotionEntity);
        $this->assertEquals(10, $promotionEntity->getDiscounts()->first()->getValue());
        $this->assertCount(1, $promotionEntity->getIndividualCodes()->getCodeArray());
    }

    /**
     * Erstellt eine Promotion und mehrere Coupons
     */
    public function testMultipleCouponCreation(): void
    {
        $promoCode = $this->simpleCouponCreator->create(['value' => 10], $this->getContext());
        $this->assertEquals(10, $promoCode->get('value'));
        $this->assertStringStartsWith('CB', $promoCode->get('code'));

        $this->simpleCouponCreator->create(['value' => 10], $this->getContext());
        $this->simpleCouponCreator->create(['value' => 10], $this->getContext());
        $this->simpleCouponCreator->create(['value' => 10], $this->getContext());
        $this->simpleCouponCreator->create(['value' => 10], $this->getContext());

        $promotionEntity = $this->getPromotionById($promoCode->get('promotion_id'));
        $this->assertInstanceOf(PromotionEntity::class, $promotionEntity);
        $this->assertEquals(10, $promotionEntity->getDiscounts()->first()->getValue());
        $this->assertCount(5, $promotionEntity->getIndividualCodes()->getCodeArray(), 'There should be 5 coupons assigned to promotion');
    }

    /**
     * Erstellt mehrere Promotions und mehrere Coupons. Promotions werden abhängig von der Payload erstellt.
     * Unterschiedliche Rabatte oder Datum-Werte führen zu mehreren Promotions.
     */
    public function testMultiplePromotionCreation(): void
    {
        $promoIds = [];
        $promoCode = $this->simpleCouponCreator->create(['value' => 10], $this->getContext());
        $this->assertEquals(10, $promoCode->get('value'));
        $this->assertStringStartsWith('CB', $promoCode->get('code'));
        $promoIds[$promoCode->get('promotion_id')] = true;
        $this->assertEquals(null, $this->getPromotionById($promoCode->get('promotion_id'))->getValidUntil()?->format('Y-m-d'));

        $promoCode = $this->simpleCouponCreator->create(['value' => 15], $this->getContext());
        $this->assertEquals(15, $promoCode->get('value'));
        $this->assertStringStartsWith('CB', $promoCode->get('code'));
        $promoIds[$promoCode->get('promotion_id')] = true;
        $this->assertEquals(null, $this->getPromotionById($promoCode->get('promotion_id'))->getValidUntil()?->format('Y-m-d'));

        $promoCode = $this->simpleCouponCreator->create(['value' => 10, 'valid_until' => '2025-01-01'], $this->getContext());
        $this->assertEquals(10, $promoCode->get('value'));
        $this->assertStringStartsWith('CB', $promoCode->get('code'));
        $promoIds[$promoCode->get('promotion_id')] = true;
        $this->assertEquals('2025-01-01', $this->getPromotionById($promoCode->get('promotion_id'))->getValidUntil()?->format('Y-m-d'));

        $promoCode = $this->simpleCouponCreator->create(['value' => 15, 'valid_until' => '2025-01-01'], $this->getContext());
        $this->assertEquals(15, $promoCode->get('value'));
        $this->assertStringStartsWith('CB', $promoCode->get('code'));
        $promoIds[$promoCode->get('promotion_id')] = true;
        $this->assertEquals('2025-01-01', $this->getPromotionById($promoCode->get('promotion_id'))->getValidUntil()?->format('Y-m-d'));

        $this->assertCount(4, $promoIds, 'There should be 4 different promotions');
    }

    /**
     * Mindestens der Wert muss übergeben werden.
     */
    public function testInvalidCouponCreation(): void
    {
        $this->expectException(MissingValueException::class);
        $this->simpleCouponCreator->create([], $this->getContext());
    }

    /**
     * Erstellt eine Promotion und einen Coupon mit mehreren Anforderungen.
     */
    public function testComplexCouponCreation(): void
    {
        $definition = [
            'name'                         => '10% Discount',
            'value'                        => 10,
            'type'                         => 'percentage',
            'active'                       => false,
            'valid_from'                   => '2020-01-01',
            'valid_until'                  => '2020-06-01',
            'max_redemptions_global'       => 100,
            'max_redemptions_per_customer' => 5,
            'individual_code_pattern'      => 'X%s%s%s%d%d%d',
        ];
        $promoCode = $this->simpleCouponCreator->create($definition, $this->getContext());
        $this->assertEquals(10, $promoCode->get('value'));
        $this->assertMatchesRegularExpression('/X[A-Z]{3}\d{3}/', $promoCode->get('code'));

        $promotionEntity = $this->getPromotionById($promoCode->get('promotion_id'));
        $discountEntity = $promotionEntity->getDiscounts()->first();
        $this->assertInstanceOf(PromotionEntity::class, $promotionEntity);

        $this->assertEquals($definition['name'], $promotionEntity->getName());
        $this->assertEquals($definition['value'], $discountEntity->getValue());
        $this->assertEquals($definition['type'], $discountEntity->getType());
        $this->assertEquals($definition['active'], $promotionEntity->isActive());
        $this->assertEquals($definition['valid_from'], $promotionEntity->getValidFrom()?->format('Y-m-d'));
        $this->assertEquals($definition['valid_until'], $promotionEntity->getValidUntil()?->format('Y-m-d'));
        $this->assertEquals($definition['max_redemptions_global'], $promotionEntity->getMaxRedemptionsGlobal());
        $this->assertEquals($definition['max_redemptions_per_customer'], $promotionEntity->getMaxRedemptionsPerCustomer());

        $this->assertCount(1, $promotionEntity->getIndividualCodes()->getCodeArray());
    }

    protected function deletePromotions(): void
    {
        $repository = $this->getRepository('promotion.repository');
        $ids = $repository->searchIds(new Criteria(), $this->getContext())->getIds();
        if (!empty($ids)) {
            $repository->delete(array_map(static fn($id) => ['id' => $id], $ids), $this->getContext());
        }
    }

    protected function getPromotionById(string $promotionId): ?PromotionEntity
    {
        $repository = $this->getRepository('promotion.repository');
        $criteria = new Criteria([$promotionId]);
        $criteria->addAssociation('individualCodes');
        $criteria->addAssociation('discounts');
        return $repository->search($criteria, $this->getContext())->first();
    }
}
