<?php

namespace MothershipSimpleApi\Tests\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\FilesystemBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\CustomField\CustomFieldEntity;


abstract class AbstractTestCase extends TestCase
{
    use KernelTestBehaviour;
    use FilesystemBehaviour;

    protected function getContext() : Context
    {
        return Context::createDefaultContext();
    }

    protected function getRepository(string $repository = 'product.repository') : EntityRepository
    {
        return $this->getContainer()->get($repository);
    }

    protected function cleanMedia()
    {
        /* @var EntityRepository $mediaRepository */
        $mediaRepository = $this->getContainer()->get('media.repository');

        $criteria = new Criteria();
        foreach ($mediaRepository->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $mediaRepository->delete([['id' => $element->getId()]], $this->getContext());
            } catch (\Exception $e) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanProduct()
    {
        /* @var EntityRepository $mediaRepository */
        $productRepository = $this->getContainer()->get('product.repository');

        $criteria = new Criteria();
        foreach ($productRepository->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $productRepository->delete([['id' => $element->getId()]], $this->getContext());
            } catch (\Exception $e) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanProperties()
    {
        /* @var EntityRepository $mediaRepository */
        $propertyGroupRepository = $this->getContainer()->get('property_group.repository');

        $criteria = new Criteria();
        foreach ($propertyGroupRepository->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $propertyGroupRepository->delete([['id' => $element->getId()]], $this->getContext());
            } catch (\Exception $e) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanCategories()
    {
        /* @var EntityRepository $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');

        $criteria = new Criteria();
        foreach ($categoryRepository->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $categoryRepository->delete([['id' => $element->getId()]], $this->getContext());
            } catch (\Exception $e) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    /**
     * Minimale Definition, um ein Produkt anzulegen
     *
     * @return array
     */
    protected function getMinimalDefinition() : array
    {
        return [
            'sku'   => 'ms-123',
            'name' => [
                'en-GB' => 'T-Shirt'
            ],
            'price' => [
                // Wert in EUR
                'EUR' => 20
            ],
            'tax'   => 19,
            'stock' => 1
        ];
    }

    /**
     * Maximale Definition
     *
     * @return array
     */
    protected function getMaximalDefinition() : array
    {
        return [
            'sku'   => 'ms-123',
            'name' => 'T-Shirt',
            'price' => [
                // Wert in EUR
                'EUR' => 20
            ],
            'tax'   => 19,
            'stock' => 1,
            'sales_channel' => [
                // Muss kein Key sein
                'default' => 'all',
                // ProductVisibilityDefinition::VISIBILITY_ALL
                'club' => 'all'
            ]
        ];
    }

    /**
     * Hilfsfunktion, um anhand der SKU das Produkt zu entfernen.
     *
     * Sollte immer dann benutzt werden, wenn es darum geht, die Datenbank in einen
     * sauberen Zustand zu versetzen.
     *
     * @param string $sku
     *
     * @return void
     */
    protected function deleteProductBySku(string $sku) : void
    {
        $productRepository = $this->getRepository('product.repository');
        $productEntity = $this->getProductBySku($sku);

        if (null !== $productEntity) {
            $productRepository->delete([
                [
                    'id' => $productEntity->getId()
                ]
            ], $this->getContext());
        }
    }

    protected function getProductBySku(string $sku) : ProductEntity|null
    {
        $productRepository = $this->getRepository('product.repository');

        $criteria = new Criteria();
        $criteria->addAssociations(['visibilities', 'media', 'properties', 'translations', 'children', 'configuratorSettings', 'categories', 'manufacturer']);
        $criteria->addFilter(new EqualsFilter('productNumber', $sku));
        $productEntity = $productRepository->search($criteria, $this->getContext())->first();
        if (null !== $productEntity) {
            return $productEntity;
        }
        return null;
    }

    protected function getPropertyGroupByCode(string $propertyGroupCode) : PropertyGroupEntity|null
    {
        $productRepository = $this->getRepository('property_group.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $propertyGroupCode));
        $propertyGroup = $productRepository->search($criteria, $this->getContext())->first();
        if (null !== $propertyGroup) {
            return $propertyGroup;
        }
        return null;
    }

    protected function getLayoutIdByType(string $type = 'product_detail') : CmsPageEntity|null
    {
        $cmsPageRepository = $this->getRepository('cms_page.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', $type));
        $cmsLayoutId = $cmsPageRepository->search($criteria, $this->getContext())->first();
        if (null !== $cmsLayoutId) {
            return $cmsLayoutId;
        }
        return null;
    }

    protected function getCustomFieldByCode(string $customFieldCode) : CustomFieldEntity|null
    {
        $customFieldRepository = $this->getRepository('custom_field.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $customFieldCode));
        $customField = $customFieldRepository->search($criteria, $this->getContext())->first();
        if (null !== $customField) {
            return $customField;
        }
        return null;
    }

    protected function getPropertyGroupOptionByCode(string $propertyGroupOptionCode) : PropertyGroupOptionEntity|null
    {
        $productRepository = $this->getRepository('property_group_option.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $propertyGroupOptionCode));
        $propertyGroupOption = $productRepository->search($criteria, $this->getContext())->first();
        if (null !== $propertyGroupOption) {
            return $propertyGroupOption;
        }
        return null;
    }

    protected function getTranslationIdByIsoCode(string $isoCode) : string
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['languages']);
        $criteria->addFilter(new EqualsFilter('code', $isoCode));
        $translationRepository = $this->getRepository('locale.repository');
        $translation = $translationRepository->search($criteria, $this->getContext())->first();

        return $translation->getLanguages()->first()->getUniqueIdentifier();
    }
}
