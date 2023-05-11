<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;

class LayoutProcessor
{
    public function process(array &$data, Product $request) : void
    {
        $data['cmsPageId'] = $request->getCmsPageId();
    }
}
