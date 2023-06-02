<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class ManufacturerNumberProcessor
{
    public function __construct(protected EntityRepositoryInterface $manufacturerRepository)
    {
    }

    public function process(array &$data, Product $request): void
    {
        $data['manufacturerNumber'] = $request->getManufacturerNumber();
    }
}
