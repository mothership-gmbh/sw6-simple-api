<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Definition;

/**
 * Creating a coupon requires information about the promotion, the discount and code.
 */
class PromotionCode
{
    public const PROPERTY_DEFINITION = [
        'code'                         => ['association' => 'individualCodes', 'field' => 'code', 'type' => 'string'],
        'payload'                      => ['association' => 'individualCodes', 'field' => 'payload', 'type' => 'array'],
        'scope'                        => ['association' => 'discounts', 'field' => 'scope', 'type' => 'string', 'default' => 'cart'],
        'type'                         => ['association' => 'discounts', 'field' => 'type', 'type' => 'string', 'default' => 'absolute'],
        'value'                        => ['association' => 'discounts', 'field' => 'value', 'type' => 'float'],
        'consider_advanced_rules'      => ['association' => 'discounts', 'field' => 'considerAdvancedRules', 'type' => 'bool', 'default' => false],
        'max_value'                    => ['association' => 'discounts', 'field' => 'maxValue', 'type' => 'float'],
        'sorter_key'                   => ['association' => 'discounts', 'field' => 'sorterKey', 'type' => 'string', 'default' => 'PRICE_ASC'],
        'applier_key'                  => ['association' => 'discounts', 'field' => 'applierKey', 'type' => 'string', 'default' => 'ALL'],
        'usage_key'                    => ['association' => 'discounts', 'field' => 'usageKey', 'type' => 'string', 'default' => 'ALL'],
        'picker_key'                   => ['association' => 'discounts', 'field' => 'pickerKey', 'type' => 'string'],
        'promotion_id'                 => ['association' => null, 'field' => 'id', 'type' => 'string'],
        'active'                       => ['association' => null, 'field' => 'active', 'type' => 'bool', 'default' => true],
        'valid_from'                   => ['association' => null, 'field' => 'validFrom', 'type' => 'datetime'],
        'valid_until'                  => ['association' => null, 'field' => 'validUntil', 'type' => 'datetime'],
        'max_redemptions_global'       => ['association' => null, 'field' => 'maxRedemptionsGlobal', 'type' => 'int'],
        'max_redemptions_per_customer' => ['association' => null, 'field' => 'maxRedemptionsPerCustomer', 'type' => 'int', 'default' => 1],
        'priority'                     => ['association' => null, 'field' => 'priority', 'type' => 'int', 'default' => 1],
        'order_count'                  => ['association' => null, 'field' => 'orderCount', 'type' => 'int'],
        'orders_per_customer_count'    => ['association' => null, 'field' => 'ordersPerCustomerCount', 'type' => 'array'],
        'exclusive'                    => ['association' => null, 'field' => 'exclusive', 'type' => 'bool'],
        'use_codes'                    => ['association' => null, 'field' => 'useCodes', 'type' => 'bool', 'default' => true],
        'customer_restriction'         => ['association' => null, 'field' => 'customerRestriction', 'type' => 'bool'],
        'prevent_combination'          => ['association' => null, 'field' => 'preventCombination', 'type' => 'bool'],
        'exclusion_ids'                => ['association' => null, 'field' => 'exclusionIds', 'type' => 'array'],
        'use_individual_codes'         => ['association' => null, 'field' => 'useIndividualCodes', 'type' => 'bool', 'default' => true],
        'individual_code_pattern'      => ['association' => null, 'field' => 'individualCodePattern', 'type' => 'string'],
        'use_setgroups'                => ['association' => null, 'field' => 'useSetgroups', 'type' => 'bool'],
        'name'                         => ['association' => null, 'field' => 'name', 'type' => 'string'],
        'custom_fields'                => ['association' => null, 'field' => 'customFields', 'type' => 'array'],
        'code_blacklist'               => ['association' => null, 'field' => null, 'type' => 'array'],
        'sales_channel'                => ['association' => null, 'field' => null, 'type' => 'array'],
    ];

    private array $data = [];

    public function assign(array $data, ?string $association = null): void
    {
        foreach (self::PROPERTY_DEFINITION as $field => $fieldDefinition) {
            if ($fieldDefinition['association'] === $association && isset($data[$fieldDefinition['field']])) {
                $this->data[$field] = $data[$fieldDefinition['field']];
            }
        }
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function get(string $field): mixed
    {
        if (isset(self::PROPERTY_DEFINITION[$field])) {
            return $this->data[$field];
        }
        return null;
    }

    public function set(string $field, mixed $value): void
    {
        if (isset(self::PROPERTY_DEFINITION[$field])) {
            $this->data[$field] = $value;
        }
    }
}
