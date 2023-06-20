<?php

namespace MothershipSimpleApi\Content\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SimpleApiPayloadCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'ms_simple_api_payload_collection';
    }

    protected function getExpectedClass(): string
    {
        return SimpleApiPayloadEntity::class;
    }
}
