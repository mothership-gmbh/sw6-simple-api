<?php

namespace MothershipSimpleApi\Content\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Teile der Entity Klasse wurden aus dem Shopware Core kopiert.
 *
 * @link \Shopware\Core\Checkout\Cart\Cart
 */
class SimpleApiPayloadEntity extends Entity
{
    use EntityIdTrait;

    protected array $payload;
    protected string $status;

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getApiAlias(): string
    {
        return 'ms-simple-api-payload';
    }
}
