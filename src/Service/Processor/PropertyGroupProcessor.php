<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use Cocur\Slugify\Slugify;
use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class PropertyGroupProcessor
{
    protected EntityRepository $propertyGroupRepository;
    protected EntityRepository $propertyGroupOptionRepository;
    protected EntityRepository $productPropertyRepository;
    protected EntityRepository $productRepository;

    public function __construct(
        EntityRepository $propertyGroupRepository,
        EntityRepository $propertyGroupOptionRepository,
        EntityRepository $productPropertyRepository,
        EntityRepository $productRepository,
    )
    {
        $this->propertyGroupRepository = $propertyGroupRepository;
        $this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
        $this->productPropertyRepository = $productPropertyRepository;
        $this->productRepository = $productRepository;
    }

    public function process(Product $request, string $productUuid, Context $context): void
    {
        $properties = $request->getProperties();
        /*
         * Die $expectedPropertyGroupOptionIds wird genutzt, um danach einen Diff durchzuführen. Es sollen nur noch option-ids
         * zugeordnet werden, die auch notwendig sind. Falsche Ids sollen entfernt werden.
         */
        $expectedPropertyGroupOptionIds = [];
        foreach ($properties as $propertyGroupCode => $propertyOptions) {
            /*
             * Die Erstellung der Property Group ist unabhängig vom aktuellen Produkt. Sie erstellt lediglich
             * die Property-Group, fügt jedoch gar keine Labels, etc. hinzu. Dies ist auch nicht die Verantwortung.
             *
             * Der Methode getPropertyGroupByCode ist ein Fallback für Projekte, bei denen es bereits die Property
             * gibt.
             */
            $propertyGroup = $this->getPropertyGroupByCode($propertyGroupCode, $context);
            if (null === $propertyGroup) {
                $propertyGroupId = $this->generatePropertyGroupId($propertyGroupCode);
            } else {
                $propertyGroupId = $propertyGroup->getId();
            }

            $propertyGroup = $this->getPropertyGroupById($propertyGroupId, $context);
            if (null === $propertyGroup) {
                $this->createPropertyGroup($propertyGroupId, $propertyGroupCode, $context);
            }

            $assignedPropertyGroupOptionsIds = $this->getAssignedPropertyGroupOptionIds($productUuid, $context);

            foreach ($propertyOptions as $propertyOptionCode) {

                // Fallback für Installationen, bei denen die Properties bereits angelegt wurden.
                $propertyGroupOption = $this->getPropertyGroupOptionByCode($propertyOptionCode, $context);
                if (null === $propertyGroupOption) {
                    $propertyGroupOptionId = self::generatePropertyGroupOptionId($propertyGroupCode, $propertyOptionCode);
                } else {
                    $propertyGroupOptionId = $propertyGroupOption->getId();
                }

                $expectedPropertyGroupOptionIds[] = $propertyGroupOptionId;
                $propertyGroupOption = $this->getPropertyGroupOptionById($propertyGroupOptionId, $context);
                if (null === $propertyGroupOption) {
                    $this->createPropertyGroupOption($propertyGroupOptionId, $propertyGroupId, $propertyOptionCode, $context);
                }

                // Sind alle Voraussetzungen dafür geschaffen, kann die jeweilige Option endlich dem Produkt zugeordnet werden.
                $this->assignToProduct($productUuid, $propertyGroupOptionId, $assignedPropertyGroupOptionsIds, $context);
            }

            /*
             * Alle invaliden Einträge werden nun anhand von der expection und tatsächlich zugeordneten entfernt
             */
            foreach ($assignedPropertyGroupOptionsIds as $assignedPropertyGroupOptionsId) {
                if (!in_array($assignedPropertyGroupOptionsId, $expectedPropertyGroupOptionIds, true)) {
                    $this->removeFromProduct($productUuid, $assignedPropertyGroupOptionsId, $context);
                }
            }
        }
    }

    protected function getPropertyGroupByCode(string $propertyGroupCode, Context $context): PropertyGroupEntity|null
    {
        $code = Slugify::create()->slugify($propertyGroupCode);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', $code));

        return $this->propertyGroupRepository->search($criteria, $context)->first();
    }

    /**
     *
     *
     * @param string $propertyGroupCode
     *
     * @return string
     */
    private function generatePropertyGroupId(string $propertyGroupCode): string
    {
        return Uuid::fromStringToHex($propertyGroupCode);
    }

    protected function getPropertyGroupById(string $propertyGroupId, Context $context): PropertyGroupEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($propertyGroupId)]);
        return $this->propertyGroupRepository->search($criteria, $context)->first();
    }

    /**
     * @param string  $propertyGroupId
     * @param string  $propertyGroupCode
     * @param Context $context
     *
     * @return void
     */
    private function createPropertyGroup(string $propertyGroupId, string $propertyGroupCode, Context $context): void
    {
        $this->propertyGroupRepository->create(
            [
                [
                    'id'           => $propertyGroupId,
                    'translations' => [
                        $context->getLanguageId() => [
                            'name'         => $propertyGroupCode,
                            'customFields' => ['code' => $propertyGroupCode],
                        ],
                    ],
                ],
            ],
            $context
        );
    }

    private function getAssignedPropertyGroupOptionIds(string $productId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['properties']);
        $criteria->addFilter(new EqualsFilter('id', $productId));

        /* @var $product ProductEntity */
        $product = $this->productRepository->search($criteria, $context)->first();

        return $product->getProperties()->getIds();
    }

    protected function getPropertyGroupOptionByCode(string $propertyGroupOptionCode, Context $context): PropertyGroupOptionEntity|null
    {
        $code = Slugify::create()->slugify($propertyGroupOptionCode);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', $code));

        return $this->propertyGroupOptionRepository->search($criteria, $context)->first();
    }

    public static function generatePropertyGroupOptionId(string $propertyGroupCode, string $propertyGroupOptionCode): string
    {
        $propertyGroupCodeUuid = Uuid::fromStringToHex($propertyGroupCode);
        $propertyGroupOptionCodeUuid = Uuid::fromStringToHex($propertyGroupOptionCode);

        return BitwiseOperations::xorHex($propertyGroupCodeUuid, $propertyGroupOptionCodeUuid);
    }

    protected function getPropertyGroupOptionById(string $propertyGroupOptionId, Context $context): PropertyGroupOptionEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($propertyGroupOptionId)]);
        return $this->propertyGroupOptionRepository->search($criteria, $context)->first();
    }

    /**
     * @param string  $propertyGroupOptionId
     * @param string  $propertyGroupId
     * @param string  $propertyGroupOptionCode
     * @param Context $context
     *
     * @return void
     */
    private function createPropertyGroupOption(string $propertyGroupOptionId, string $propertyGroupId, string $propertyGroupOptionCode, Context $context): void
    {
        $this->propertyGroupOptionRepository->create(
            [
                [
                    'id'           => $propertyGroupOptionId,
                    'groupId'      => $propertyGroupId,
                    'translations' => [
                        $context->getLanguageId() => [
                            'name'         => $propertyGroupOptionCode,
                            'customFields' => ['code' => $propertyGroupOptionCode],
                        ],
                    ],
                ],
            ],
            $context
        );
    }

    private function assignToProduct(string $productId, string $propertyGroupOptionId, array $assignedPropertyIds, Context $context): void
    {
        if (!in_array($propertyGroupOptionId, $assignedPropertyIds, true)) {
            $this->productPropertyRepository->create(
                [[
                    'productId' => $productId,
                    'optionId'  => $propertyGroupOptionId,
                ]],
                $context
            );
        }
    }

    private function removeFromProduct(string $productId, string $propertyGroupOptionId, Context $context): void
    {
        $this->productPropertyRepository->delete(
            [[
                'productId' => $productId,
                'optionId'  => $propertyGroupOptionId,
            ]],
            $context
        );
    }
}
