<?php

namespace MothershipSimpleApiTests\Service\Validator;


use MothershipSimpleApi\Service\Definition\Product\Request;
use MothershipSimpleApiTests\Service\AbstractTestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

abstract class AbstractValidatorTest extends AbstractTestCase
{
    use KernelTestBehaviour;

    protected Request $request;


    protected function setUp(): void
    {
        $this->request = $this->getContainer()->get(Request::class);
    }
}
