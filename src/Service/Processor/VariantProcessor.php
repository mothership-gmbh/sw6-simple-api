<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Request;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use Shopware\Core\Content\Product\Aggregate\ProductConfiguratorSetting\ProductConfiguratorSettingCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;


class VariantProcessor
{
    public function __construct(
        protected EntityRepository $productRepository,
        protected EntityRepository $productConfiguratorRepository
    )
    {
    }

    // array &$data,
    public function process(Request $request, Context $context): void
    {
        $parentUuid =  Uuid::fromStringToHex($request->getProduct()->getSku());
        $this->determineVariantsToBeDeleted($request, $context);

        foreach ($request->getVariants() as $variantProduct) {
            $variantUuid = Uuid::fromStringToHex($variantProduct->getSku());
            $variant     = $this->loadEntityById($variantUuid, $context);


            $productConfiguratorSettings = $this->loadProductConfiguratorSettingsByProductId($parentUuid, $context);
            $dataConfiguratorSettings = [
                'id' => $parentUuid,
                'configuratorSettings' => []
            ];

            $dataOptions = [
                'id'       => $variantUuid,
                'parentId' => $parentUuid,
                'options'  => []
            ];

            foreach ($variantProduct->getAxis() as $propertyGroupCode => $propertyOptions) {
                /*
                 * Die Erstellung der Property Group ist unabhängig vom aktuellen Produkt. Sie erstellt lediglich
                 * die Property-Group, fügt jedoch gar keine Labels, etc. hinzu. Dies ist auch nicht die Verantwortung.
                 *
                 * @link https://stackoverflow.com/questions/74644171/how-to-import-products-with-variations-in-shopware-6
                 */
                foreach ($propertyOptions as $propertyOptionCode) {
                    $propertyGroupOptionId = PropertyGroupProcessor::generatePropertyGroupOptionId($propertyGroupCode, $propertyOptionCode);
                    $dataConfiguratorSettings['configuratorSettings'][] = [
                        'optionId' => $propertyGroupOptionId,
                        'id'       => BitwiseOperations::xorHex($parentUuid, $propertyGroupOptionId)

                    ];
                    $dataOptions['options'][] = [
                        'id' => $propertyGroupOptionId
                    ];
                }
            }

            $this->productRepository->update([$dataConfiguratorSettings], $context);
            $this->productRepository->update([$dataOptions], $context);
        }
    }

    protected function determineVariantsToBeDeleted(Request $request, Context $context)
    {
        $parentUuid       =  Uuid::fromStringToHex($request->getProduct()->getSku());
        $assignedVariants = $this->loadEntityById($parentUuid, $context)->getChildren();
        $expectedVariants = $request->getVariants();
        $expectedVariantsSku = [];
        foreach ($expectedVariants as $expectedVariant) {
            $expectedVariantsSku[] = $expectedVariant->getSku();
        }

        foreach ($assignedVariants as $assignedVariant) {
            if ($assignedVariant->getProductNumber() && !in_array(
                    $assignedVariant->getProductNumber(),
                    $expectedVariantsSku,
                    true
                )) {
                $this->productRepository->delete([['id' => $assignedVariant->getId()]], $context);
            }
        }
    }

    protected function loadEntityById(string $productUuid, Context $context): ProductEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['configuratorSettings', 'children']);
        $criteria->addFilter(new EqualsFilter('id', $productUuid));
        return $this->productRepository->search($criteria, $context)->first();
    }

    protected function loadProductConfiguratorSettingsByProductId(string $productUuid, Context $context) : array
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['configuratorSettings']);
        $criteria->addFilter(new EqualsFilter('productId', $productUuid));
        return $this->productConfiguratorRepository->search($criteria, $context)->getElements();
    }
}
