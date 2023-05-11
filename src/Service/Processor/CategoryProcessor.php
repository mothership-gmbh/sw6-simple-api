<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CategoryProcessor
{
    public function __construct(
        protected EntityRepositoryInterface $categoryRepository,
        protected EntityRepositoryInterface $productRepository,
        protected EntityRepository          $productCategoryRepository,
    )
    {
    }

    /**
     * @throws InvalidSalesChannelNameException
     */
    public function process(array &$data, Product $request, string $productUuid, Context $context): void
    {
        $categories = $request->getCategories();
        $expectedCategories = [];
        $data['categories'] = [];
        if (null !== $categories) {
            foreach ($categories as $category) {
                $categoryId = $this->getCategoryByCode($category, $context);
                $data['categories'][] = ['id' => $categoryId];
                $expectedCategories[] = $categoryId;
            }

            $assignedCategories = $this->getAssignedCategories($productUuid, $context);
            if (!empty($assignedCategories)) {
                $this->cleanup($productUuid, $assignedCategories, $expectedCategories, $context);
            }
        }
    }

    /**
     * @throws InvalidSalesChannelNameException
     */
    protected function getCategoryByCode(string $categoryCode, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', $categoryCode));

        $categoryId = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        if (null === $categoryId) {
            throw new InvalidSalesChannelNameException('There is no category with the code [' . $categoryCode . '] in the table category');
        }
        return $categoryId;
    }

    protected function getAssignedCategories(string $productUuid, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $productUuid));
        $criteria->addAssociation('categories');

        $assignedCategoryIds = [];
        $products = $this->productRepository->search($criteria, $context)->first();

        if (null === $products) {
            return [];
        }

        $categories = $products->getCategories();
        if ($categories->count() > 0) {
            foreach ($categories as $category) {
                $assignedCategoryIds[] = $category->getId();
            }
        }
        return $assignedCategoryIds;
    }

    protected function cleanup(string $productId, array $assignedCategories, array $expectedCategories, Context $context): void
    {
        $requiresCleanup = false;
        if (count($assignedCategories) !== count($expectedCategories)) {
            $requiresCleanup = true;
        }
        if (count(array_diff($assignedCategories, $expectedCategories)) > 0) {
            $requiresCleanup = true;
        }
        if (count(array_diff($expectedCategories, $assignedCategories)) > 0) {
            $requiresCleanup = true;
        }
        if ($requiresCleanup) {
            foreach ($assignedCategories as $assignedCategoryId) {
                $this->productCategoryRepository->delete([['productId' => $productId, 'categoryId' => $assignedCategoryId]], $context);
            }
        }
    }
}
