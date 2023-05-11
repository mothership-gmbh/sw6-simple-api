<?php

namespace MothershipSimpleApi\Tests\Service\Validator;

use JsonException;
use MothershipSimpleApi\Service\Validator\Exception\Image\DuplicatedCoverAssignmentException;
use MothershipSimpleApi\Service\Validator\Exception\Image\DuplicatedUrlException;
use MothershipSimpleApi\Service\Validator\Exception\Image\InvalidDataTypeException;
use MothershipSimpleApi\Service\Validator\Exception\Image\MissingUrlKeyException;

class ImageValidatorTest extends AbstractValidatorTest
{
    /**
     * Bilder müssen immer als Array mit konf. Parameter übergeben werden.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Image
     * @group SimpleApi_Product_Validator_Image_1
     * @throws JsonException
     */
    public function imageConfigurationMissing(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['images'] = [
            'url' => '123'
        ];

        $this->expectException(InvalidDataTypeException::class);
        $this->request->init($definition);
    }

    /**
     * If you pass an url but
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Image
     * @group SimpleApi_Product_Validator_Image_2
     * @throws JsonException
     */
    public function urlIsMissing(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['images'] = [
            'url' => [
                'unnecessary_parameter' => 'i_am_not_required'
            ]
        ];

        $this->expectException(MissingUrlKeyException::class);
        $this->request->init($definition);
    }

    /**
     * URLs sind identisch. Dies führt zu einer Exception
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Image
     * @group SimpleApi_Product_Validator_Image_3
     * @throws JsonException
     */
    public function duplicatedUrls(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true
            ],
            [
                'url'     => 'https://via.placeholder.com/50x50.png'
            ]
        ];

        $this->expectException(DuplicatedUrlException::class);
        $this->request->init($definition);
    }

    /**
     * Es kann nur ein Bild das Cover-Bild sein
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Validator
     * @group SimpleApi_Product_Validator_Image
     * @group SimpleApi_Product_Validator_Image_4
     * @throws JsonException
     */
    public function duplicatedCoverIsNotPossible(): void
    {
        $definition = $this->getMinimalDefinition();
        $definition['images'] = [
            [
                'url'     => 'https://via.placeholder.com/50x50.png',
                'isCover' => true
            ],
            [
                'url'     => 'https://via.placeholder.com/51x51.png',
                'isCover' => true
            ]
        ];

        $this->expectException(DuplicatedCoverAssignmentException::class);
        $this->request->init($definition);
    }
}
