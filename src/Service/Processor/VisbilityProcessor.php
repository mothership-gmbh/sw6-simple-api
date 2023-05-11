<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class VisbilityProcessor
{
    protected EntityRepositoryInterface $salesChannelRepository;
    protected EntityRepositoryInterface $productVisibilityRepository;

    public function __construct(EntityRepositoryInterface $salesChannelRepository, EntityRepositoryInterface $productVisibilityRepository)
    {
        $this->salesChannelRepository = $salesChannelRepository;
        $this->productVisibilityRepository = $productVisibilityRepository;
    }

    /**
     * @throws InvalidSalesChannelNameException
     */
    public function process(array &$data, Product $request, string $productUuid, Context $context): void
    {
        $mapping = [
            'all'    => ProductVisibilityDefinition::VISIBILITY_ALL,
            'link'   => ProductVisibilityDefinition::VISIBILITY_LINK,
            'search' => ProductVisibilityDefinition::VISIBILITY_SEARCH,
        ];
        $salesChannel = $request->getSalesChannel();
        $salesChannelIds = [];

        if (null !== $salesChannel) {
            $data['visibilities'] = [];
            foreach ($salesChannel as $salesChannelName => $visibility) {
                $salesChannelId = $this->getSalesChannelByName($salesChannelName, $context);
                $salesChannelIds[] = $salesChannelId;
                $data['visibilities'][] = [
                    'salesChannelId' => $salesChannelId,
                    'visibility'     => $mapping[$visibility],
                ];
            }
        }

        $assignedSalesChannels = $this->getAssignedSalesChannels($productUuid, $context);
        if (!empty($assignedSalesChannels)) {
            $this->cleanup($assignedSalesChannels, $salesChannelIds, $context);
        }
    }

    /**
     * @throws InvalidSalesChannelNameException
     */
    protected function getSalesChannelByName(string $salesChannelName, Context $context): string
    {
        $criteria = new Criteria();

        // Bei default ist der Sales-Channel einfach leer.
        if ($salesChannelName === 'default') {
            $criteria->addFilter(new EqualsFilter('name', null));
        } else {
            $criteria->addFilter(new EqualsFilter('name', $salesChannelName));
        }

        $salesChannelId = $this->salesChannelRepository->searchIds($criteria, $context)->firstId();
        if (null === $salesChannelId) {
            throw new InvalidSalesChannelNameException('There is no sales-channel [' . $salesChannelName . '] in the table sales_channel');
        }
        return $salesChannelId;
    }

    protected function getAssignedSalesChannels(string $productUuid, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productUuid));
        return $this->productVisibilityRepository->searchIds($criteria, $context)->getIds();
    }

    protected function cleanup(array $assignedSalesChannels, array $salesChannels, Context $context): void
    {
        $requiresCleanup = false;
        if (count($assignedSalesChannels) !== count($salesChannels)) {
            $requiresCleanup = true;
        }
        if (count(array_diff($assignedSalesChannels, $salesChannels)) > 0) {
            $requiresCleanup = true;
        }
        if (count(array_diff($salesChannels, $assignedSalesChannels)) > 0) {
            $requiresCleanup = true;
        }
        if ($requiresCleanup) {
            foreach ($assignedSalesChannels as $assignedSalesChannel) {
                $this->productVisibilityRepository->delete([['id' => $assignedSalesChannel]], $context);
            }
        }
    }
}
