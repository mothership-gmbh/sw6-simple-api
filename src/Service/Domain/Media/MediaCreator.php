<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Domain\Media;

use MothershipSimpleApi\Service\Domain\Media\Processor\ImageProcessor;
use Shopware\Core\Framework\Context;

class MediaCreator
{
    public function __construct(protected ImageProcessor $imageProcessor, protected MediaRequest $request)
    {
    }

    /**
     * @param array   $definition
     * @param Context $context
     *
     * @return string
     */
    public function createEntity(array $definition, Context $context): string
    {
        $this->request->init($definition);
        return $this->imageProcessor->process($this->request->getMedia(), $context);
    }
}
