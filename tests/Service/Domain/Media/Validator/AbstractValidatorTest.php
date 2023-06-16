<?php

namespace MothershipSimpleApiTests\Service\Domain\Media\Validator;


use MothershipSimpleApi\Service\Domain\Media\MediaRequest;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

abstract class AbstractValidatorTest extends \MothershipSimpleApiTests\Service\Domain\Media\AbstractTestCase
{
    use KernelTestBehaviour;

    protected MediaRequest $request;


    protected function setUp(): void
    {
        $this->request = $this->getContainer()->get(MediaRequest::class);
    }
}
