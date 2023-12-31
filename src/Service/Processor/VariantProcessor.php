<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Processor;

use MothershipSimpleApi\Service\Definition\Product\Request;
use MothershipSimpleApi\Service\Exception\ProductNotFoundException;
use MothershipSimpleApi\Service\Exception\PropertyGroupOptionNotFoundException;
use MothershipSimpleApi\Service\Helper\BitwiseOperations;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
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
        protected EntityRepository $propertyGroupOptionRepository,
        protected EntityRepository $propertyGroupRepository,
        protected EntityRepository $productOptionRepository
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
        $assignedVariants = $this->loadEntityById($parentUuid, $context)?->getChildren();
        $expectedVariants = $request->getVariants();
        $expectedVariantsSku = [];
        foreach ($expectedVariants as $expectedVariant) {
            $expectedVariantsSku[$expectedVariant->getSku()] = $expectedVariant->getAxis();
        }

        $assignedVariantsOptions = $this->transformShopwareOptionsToSimpleApiAxisFormat($assignedVariants?->getElements(), $context);

        foreach ($assignedVariants as $assignedVariant) {
            $sku = $assignedVariant->getProductNumber();
            if ($assignedVariant->getProductNumber()
                    && (!array_key_exists(
                        $assignedVariant->getProductNumber(),
                        $expectedVariantsSku,
                )
                )) {
                $this->productRepository->delete([['id' => $assignedVariant->getId()]], $context);
            }
            /*
             * Hat die neue erwartete Variante andere Variantenachsen als die bestehende Variante mit der gleichen SKU,
             * sollen die bestehenden Variantenachsen gelöscht werden.
             * Informationen zu den Variantenachsen sind in Shopware in 2 speziellen Tabellen
             * (product_configurator_setting & product_option) gespeichert.
             * Hinweis: Wir dürfen hier nicht einfach das Variantenprodukt löschen, da die product-Entität mit
             * Bestellungen in der order-Tabelle verknüpft ist.
             */
            if (isset($assignedVariantsOptions[$sku], $expectedVariantsSku[$sku])
                && !$this->arraysAreEqual($assignedVariantsOptions[$sku], $expectedVariantsSku[$sku])) {
                $this->deleteProductConfiguratorSettings($parentUuid, $context);
                $this->deleteProductOptions($assignedVariant->getId(), $context);
            }
        }
    }

    protected function loadEntityById(string $productUuid, Context $context): ?ProductEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['configuratorSettings', 'children']);
        $criteria->addFilter(new EqualsFilter('id', $productUuid));
        return $this->productRepository->search($criteria, $context)?->first();
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

    protected function getPropertyGroupById(string $propertyGroupId, Context $context): PropertyGroupEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($propertyGroupId)]);
        return $this->propertyGroupRepository->search($criteria, $context)->first();
    }

    protected function getPropertyGroupOptionByCode(string $propertyGroupOptionCode, Context $context): PropertyGroupOptionEntity|null
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.code', $propertyGroupOptionCode));

        return $this->propertyGroupOptionRepository->search($criteria, $context)->first();
    }

    /**
     * In Shopware gibt es konfigurierbare (Parent-)Produkte und einfache Produkte die jeweils Optionen/Varianten darstellen.
     * Die Informationen über die Optionen eines konf. Produktes werden in Shopware als UUIDs dargestellt und sind daher schwer verständlich.
     * Im Payload der SimpleAPI sind die Variantenachsen-Informationen hingegen maximal verständlich gehalten.
     * Daher transformieren wir die Informationen aus Shopware in das Format der SimpleAPI.
     */
    protected function transformShopwareOptionsToSimpleApiAxisFormat(array $assignedVariants, Context $context): array
    {
        $transformedVariants = [];
        foreach ($assignedVariants as $assignedVariant) {
            $optionIds = $assignedVariant->getOptionIds();
            $sku = $assignedVariant->getProductNumber();
            $transformedVariants[$sku] = [];
            foreach ($optionIds as $optionId) {
                $option = $this->getPropertyGroupOptionById($optionId, $context);
                $groupId = $option?->getGroupId();
                $groupCode = $this->getPropertyGroupById($groupId, $context)?->getCustomFields()['code'];
                if (!array_key_exists($groupCode, $transformedVariants[$sku])) {
                    $transformedVariants[$sku][$groupCode] = [];
                }
                $optionCode = $option?->getCustomFields()['code'];
                $transformedVariants[$sku][$groupCode][] = $optionCode;
            }
        }
        return $transformedVariants;
    }

    protected function arraysAreEqual(array $assigned, array $expected): bool
    {
        foreach ($assigned as $groupCode => $value) {
            sort($assigned[$groupCode]);
            if (array_key_exists($groupCode, $expected)) {
                sort($expected[$groupCode]);
                } else {
                return false;
            }
        }

        return $assigned === $expected;
    }

    protected function deleteProductConfiguratorSettings(string $parentUuid, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['configuratorSettings']);
        $criteria->addFilter(new EqualsFilter('productId', $parentUuid));
        $productConfiguratorSettings = $this->productConfiguratorRepository->searchIds($criteria, $context)->getIds();
        $toDelete = [];
        foreach ($productConfiguratorSettings as $productConfiguratorSetting) {
            $primaryKey = [];
            $primaryKey['id'] = $productConfiguratorSetting;
            $toDelete[] = $primaryKey;
        }
        $this->productConfiguratorRepository->delete($toDelete, $context);
    }

    protected function deleteProductOptions(string $variantUuid, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['configuratorSettings']);
        $criteria->addFilter(new EqualsFilter('productId', $variantUuid));
        $productOptions = $this->productOptionRepository->searchIds($criteria, $context)->getIds();
        $this->productOptionRepository->delete($productOptions, $context);
    }
}
