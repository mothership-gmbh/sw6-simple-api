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
        $this->cleanMedia();
        $this->cleanProduct();
        $this->cleanProperties();
        $this->cleanCategories();
    }
}
