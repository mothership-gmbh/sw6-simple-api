<?php

namespace MothershipSimpleApiTests\Service\Domain\Media\Processor;

use MothershipSimpleApi\Service\Domain\Media\MediaCreator;
use MothershipSimpleApiTests\Service\AbstractTestCase;

abstract class AbstractProcessorTest extends AbstractTestCase
{
    protected MediaCreator $mediaCreator;

    protected function setUp(): void
    {
        $this->mediaCreator = $this->getContainer()->get(MediaCreator::class);
        $this->clearRepository('media.repository');
        $this->clearRepository('product.repository');
        $this->clearRepository('category.repository');
        $this->clearRepository('property_group.repository');
        $this->clearRepository('custom_field.repository');
        $this->clearRepository('custom_field_set.repository');
        $this->clearRepository('custom_field_set_relation.repository');
    }

    /**
     * Minimale Definition, um ein Produkt anzulegen
     *
     * @return array
     */
    protected function getMinimalDefinition(): array
    {
        return  [
            'url'               => 'https://via.placeholder.com/50x50.png',
            'media_folder_name' => 'Category Media',
        ];
    }
}
