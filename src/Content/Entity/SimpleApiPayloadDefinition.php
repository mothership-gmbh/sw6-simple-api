<?php

namespace MothershipSimpleApi\Content\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class SimpleApiPayloadDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'ms_simple_api_payload';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return SimpleApiPayloadCollection::class;
    }

    public function getEntityClass(): string
    {
        return SimpleApiPayloadEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new JsonField('payload', 'payload'))->addFlags(new ApiAware(), new Required()),
            (new StringField('status', 'status'))->addFlags(new ApiAware(), new Required()),
            (new StringField('error', 'error'))->addFlags(new ApiAware()),
        ]);
    }
}
