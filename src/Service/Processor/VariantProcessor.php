<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product\Request;
use MothershipSimpleApi\Service\Exception\ProductNotFoundException;
use MothershipSimpleApi\Service\Exception\PropertyGroupOptionNotFoundException;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;


class VariantProcessor
{
    public function __construct(
        protected EntityRepository $productRepository,
        protected EntityRepository $productConfiguratorRepository,
        protected EntityRepository $propertyGroupOptionRepository
    )
    {
    }

    /**
     * @throws PropertyGroupOptionNotFoundException
     * @throws ProductNotFoundException
     */
    public function process(Request $request, Context $context): void
    {
        $parentUuid = $this->getProductUuid($request->getProduct()->getSku(), $context);
        $this->determineVariantsToBeDeleted($parentUuid, $request, $context);

        foreach ($request->getVariants() as $variantProduct) {
            $variantUuid = $this->getProductUuid($variantProduct->getSku(), $context);
            $this->loadEntityById($variantUuid, $context);


            $this->loadProductConfiguratorSettingsByProductId($parentUuid, $context);
            $dataConfiguratorSettings = [
                'id'                   => $parentUuid,
                'configuratorSettings' => [],
            ];

            $dataOptions = [
                'id'       => $variantUuid,
                'parentId' => $parentUuid,
                'options'  => [],
            ];

            foreach ($variantProduct->getAxis() as $propertyGroupCode => $propertyOptions) {
                /*
                 * Die Erstellung der Property Group ist unabhängig vom aktuellen Produkt. Sie erstellt lediglich
                 * die Property-Group, fügt jedoch gar keine Labels, etc. hinzu. Dies ist auch nicht die Verantwortung.
                 *
                 * @link https://stackoverflow.com/questions/74644171/how-to-import-products-with-variations-in-shopware-6
                 */
                foreach ($propertyOptions as $propertyOptionCode) {
                    $propertyGroupOptionId = $this->getPropertyGroupOptionId($propertyGroupCode, $propertyOptionCode, $context);
                    $dataConfiguratorSettings['configuratorSettings'][] = [
                        'optionId' => $propertyGroupOptionId,
                        'id'       => BitwiseOperations::xorHex($parentUuid, $propertyGroupOptionId),

                    ];
                    $dataOptions['options'][] = [
                        'id' => $propertyGroupOptionId,
                    ];
                }
            }

            $this->productRepository->update([$dataConfiguratorSettings], $context);
            $this->productRepository->update([$dataOptions], $context);
        }
    }

    protected function determineVariantsToBeDeleted(string $parentUuid, Request $request, Context $context): void
    {
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

    protected function loadProductConfiguratorSettingsByProductId(string $productUuid, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['configuratorSettings']);
        $criteria->addFilter(new EqualsFilter('productId', $productUuid));
        return $this->productConfiguratorRepository->search($criteria, $context)->getElements();
    }

    /**
     * Der Processor kümmert sich nur um die Zuweisung der Varianten zu den konfigurierbaren Parent-Produkten.
     * An dieser Stelle müssen also sowohl die Varianten als auch die Parent-Produkte bereits vorhanden sein.
     * Wir suchen daher nach dem bestehenden Produkt in der Shopware-Datenbank.
     * Gibt es das Produkt nicht, hat etwas im SimpleProductCreator nicht funktioniert und wir werfen eine Exception.
     *
     * @throws ProductNotFoundException
     */
    protected function getProductUuid(string $sku, Context $context): string
    {
        // Standardverhalten, macht aber nur Sinn, wenn Product mit nachvollziehbarer UUID erstellt wurde.
        $productUuid = Uuid::fromStringToHex($sku);
        // Wir testen, ob es das Product mit der nachvollziehbaren UUID wirklich gibt.
        $product = $this->getProductById($productUuid, $context);
        // Fallback: wir versuchen das Product noch über die SKU zu laden.
        if (null === $product) {
            $product = $this->getProductBySku($sku, $context);
            if (null === $product) {
                throw new ProductNotFoundException("Product with sku [$sku] not found in database.");
            }
            $productUuid = $product->getId();
        }
        return $productUuid;
    }

    protected function getProductById(string $productUuid, Context $context): ProductEntity|null
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $productUuid));
        return $this->productRepository->search($criteria, $context)->first();
    }

    protected function getProductBySku(string $sku, Context $context): ProductEntity|null
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $sku));
        return $this->productRepository->search($criteria, $context)->first();
    }

    /**
     * @throws PropertyGroupOptionNotFoundException
     */
    protected function getPropertyGroupOptionId($propertyGroupCode, $propertyOptionCode, $context): string
    {
        // Standardverhalten, macht aber nur Sinn, wenn PropertyGroupOption mit nachvollziehbarer UUID erstellt wurde.
        $propertyGroupOptionId = PropertyGroupProcessor::generatePropertyGroupOptionId($propertyGroupCode, $propertyOptionCode);
        // Wir testen, ob es die PropertyGroupOption mit der nachvollziehbaren UUID wirklich gibt.
        $propertyGroupOption = $this->getPropertyGroupOptionById($propertyGroupOptionId, $context);
        // Fallback: wir versuchen die PropertyGroupOption noch über das customField 'code' zu laden.
        if (null === $propertyGroupOption) {
            $propertyGroupOption = $this->getPropertyGroupOptionByCode($propertyOptionCode, $context);
            if (null === $propertyGroupOption) {
                throw new PropertyGroupOptionNotFoundException("There is no PropertyGroupOption with code [$propertyOptionCode] in the database.");
            }
            $propertyGroupOptionId = $propertyGroupOption->getId();
        }
        return $propertyGroupOptionId;
    }

    protected function getPropertyGroupOptionById(string $propertyGroupOptionId, Context $context): PropertyGroupOptionEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($propertyGroupOptionId)]);
        return $this->propertyGroupOptionRepository->search($criteria, $context)->first();
    }

    protected function getPropertyGroupOptionByCode(string $propertyGroupOptionCode, Context $context): PropertyGroupOptionEntity|null
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', $propertyGroupOptionCode));

        return $this->propertyGroupOptionRepository->search($criteria, $context)->first();
    }
}
