<?php

namespace MothershipSimpleApiTests\Service\Validator;

use MothershipSimpleApi\Service\Validator\Exception\SalesChannel\InvalidSalesChannelVisibilityException;

class SalesChannelValidatorTest extends AbstractValidatorTest
{
    /**
     * Jeder Kanal braucht eine Sichtbarkeit
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_SalesChannel
     * @group SimpleApi_Product_Validator_SalesChannel_1
     *
     */
    public function channelWithoutVisibilityWillThrowException(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['sales_channel'] = [
            'default',
        ];

        $this->expectException(InvalidSalesChannelVisibilityException::class);
        $this->request->init($definition);
    }

    /**
     * Sichbtarkeit ist auf 'all' gestellt.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_SalesChannel
     * @group SimpleApi_Product_Validator_SalesChannel_2
     *
     */
    public function channelHasValidVisibility(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['sales_channel'] = [
            'default' => 'all',
        ];

        $this->request->init($definition);
        $this->assertTrue(true); // Es wird keine Exception geworfen
    }
}
