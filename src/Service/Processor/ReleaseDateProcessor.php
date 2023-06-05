<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;

class ReleaseDateProcessor
{
    public function process(array &$data, Product $request): void
    {
        $data['releaseDate'] = $request->getReleaseDate();
    }
}
