<?php

namespace MothershipSimpleApiTests\Service\Processor;

use MothershipSimpleApi\Service\SimpleProductCreator;
use MothershipSimpleApiTests\Service\AbstractTestCase;

abstract class AbstractProcessorTest extends AbstractTestCase
{
    protected SimpleProductCreator $simpleProductCreator;

    protected function setUp(): void
    {
        $this->simpleProductCreator = $this->getContainer()->get(SimpleProductCreator::class);
        $this->clearRepository('media.repository');
        $this->clearRepository('product.repository');
        $this->clearRepository('category.repository');
        $this->clearRepository('property_group.repository');
        $this->clearRepository('custom_field.repository');
        $this->clearRepository('custom_field_set.repository');
        $this->clearRepository('custom_field_set_relation.repository');
    }
}
