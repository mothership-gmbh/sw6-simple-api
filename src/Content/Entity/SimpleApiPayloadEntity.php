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

    protected array $data;
    protected string $status;

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getApiAlias(): string
    {
        return 'ms-simple-api-payload';
    }
}
