<?php

namespace MothershipSimpleApiTests\Service\Domain\Media\Validator;

use MothershipSimpleApi\Service\Domain\Media\Validator\Exception\Image\MissingMediaFolderNameException;
use MothershipSimpleApi\Service\Domain\Media\Validator\Exception\Image\MissingUrlException;

class ImageValidatorTest extends AbstractValidatorTest
{
    /**
     * Es muss immer ein valider Key hinterlegt werden
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Domain
     * @group SimpleApi_Domain_Media
     * @group SimpleApi_Domain_Media_Validator
     * @group SimpleApi_Domain_Media_Validator_Image
     * @group SimpleApi_Domain_Media_Validator_Image_1
     */
    public function invalidImageConfiguration(): void
    {
        $definition =  [
            'unknown_key' => 'https://via.placeholder.com/50x50.png',
        ];
        $this->expectException(MissingUrlException::class);
        $this->request->init($definition);
    }

    /**
     * Ein Bild muss auch immer einem Verzeichnis zugeordnet werden.
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Domain
     * @group SimpleApi_Domain_Media
     * @group SimpleApi_Domain_Media_Validator
     * @group SimpleApi_Domain_Media_Validator_Image
     * @group SimpleApi_Domain_Media_Validator_Image_2
     */
    public function missingMediaFolderKey(): void
    {
        $definition =  [
            'url' => 'https://via.placeholder.com/50x50.png',
        ];
        $this->expectException(MissingMediaFolderNameException::class);
        $this->request->init($definition);
    }
}
