<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use Cocur\Slugify\Slugify;
use MothershipSimpleApi\Service\Definition\Product;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class ManufacturerProcessor
{
    public function __construct(protected EntityRepositoryInterface $manufacturerRepository)
    {
    }

    public function process(array &$data, Product $request, Context $context): void
    {
        $manufacturer = $request->getManufacturer();
        $data['manufacturerId'] = null;

        if (null !== $manufacturer) {
            $manufacturerId = $this->getManufacturerByCode($manufacturer, $context);
            if (empty($manufacturerId)) {
                $this->createManufacturer($manufacturer, $context);
            }
            $data['manufacturerId'] = $manufacturerId;
        }
    }

    protected function getManufacturerByCode(string $manufacturerName, Context $context): string|null
    {
        $code = Slugify::create()->slugify($manufacturerName);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', $code));

        $manufacturerId = $this->manufacturerRepository->searchIds($criteria, $context)->firstId();
        return $manufacturerId ?? null;
    }

    protected function createManufacturer(string $manufacturerName, Context $context): string
    {
        $code = Slugify::create()->slugify($manufacturerName);
        $data = [
            'id'           => Uuid::fromStringToHex($code),
            'name'         => $manufacturerName,
            'customFields' => [
                'code' => $code,
            ],
        ];
        $this->manufacturerRepository->create([$data], $context);
        return $data['id'];
    }
}
