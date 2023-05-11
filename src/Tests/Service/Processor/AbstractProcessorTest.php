<?php

namespace MothershipSimpleApi\Tests\Service\Processor;

use MothershipSimpleApi\Service\SimpleProductCreator;
use MothershipSimpleApi\Tests\Service\AbstractTestCase;

abstract class AbstractProcessorTest extends AbstractTestCase
{
    protected SimpleProductCreator $simpleProductCreator;

    protected function setUp(): void
    {
        $this->simpleProductCreator = $this->getContainer()->get(\MothershipSimpleApi\Service\SimpleProductCreator::class);
        $this->cleanMedia();
        $this->cleanProduct();
        $this->cleanProperties();
        $this->cleanCategories();
    }
}
