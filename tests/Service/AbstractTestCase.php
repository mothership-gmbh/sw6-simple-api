<?php

namespace MothershipSimpleApiTests\Service;

use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Media\MediaEntity;
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

    protected function cleanMedia(): void
    {
        /* @var EntityRepository $mediaRepository */
        $mediaRepository = $this->getContainer()->get('media.repository');

        $criteria = new Criteria();
        foreach ($mediaRepository->search($criteria, $this->getContext())->getElements() as $element) {
            /** @var MediaEntity $element */
            try {
                $mediaRepository->delete([['id' => $element->getId()]], $this->getContext());
            } catch (Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function getContext(): Context
    {
        return Context::createDefaultContext();
    }

    protected function cleanProduct(): void
    {
        /* @var EntityRepository $mediaRepository */
        $productRepository = $this->getContainer()->get('product.repository');

        $criteria = new Criteria();
        foreach ($productRepository?->search($criteria, $this->getContext())->getElements() as $element) {
            /** @var ProductEntity $element */
            try {
                $productRepository?->delete([['id' => $element->getId()]], $this->getContext());
            } catch (Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanProperties(): void
    {
        /* @var EntityRepository $mediaRepository */
        $propertyGroupRepository = $this->getContainer()->get('property_group.repository');

        $criteria = new Criteria();
        foreach ($propertyGroupRepository?->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $propertyGroupRepository?->delete([['id' => $element->getId()]], $this->getContext());
            } catch (Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanCustomFields(): void
    {
        /* @var EntityRepository $mediaRepository */
        $customFieldRepository = $this->getContainer()->get('custom_field.repository');

        $criteria = new Criteria();
        foreach ($customFieldRepository?->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $customFieldRepository?->delete([['id' => $element->getId()]], $this->getContext());
            } catch (Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanCustomFieldSets(): void
    {
        /* @var EntityRepository $mediaRepository */
        $customFieldSetRepository = $this->getContainer()->get('custom_field_set.repository');

        $criteria = new Criteria();
        foreach ($customFieldSetRepository?->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $customFieldSetRepository?->delete([['id' => $element->getId()]], $this->getContext());
            } catch (Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanCustomFieldSetRelations(): void
    {
        /* @var EntityRepository $mediaRepository */
        $customFieldSetRelationRepository = $this->getContainer()->get('custom_field_set_relation.repository');

        $criteria = new Criteria();
        foreach ($customFieldSetRelationRepository?->search($criteria, $this->getContext())->getElements() as $element) {
            try {
                $customFieldSetRelationRepository?->delete([['id' => $element?->getId()]], $this->getContext());
            } catch (Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    protected function cleanCategories(): void
    {
        /* @var EntityRepository $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');

        $criteria = new Criteria();
        foreach ($categoryRepository->search($criteria, $this->getContext())->getElements() as $element) {
            /** @var CategoryEntity $element */
            try {
                $categoryRepository->delete([['id' => $element->getId()]], $this->getContext());
            } catch (Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }

    /**
     * Minimale Definition, um ein Produkt anzulegen
     *
     * @return array
     */
    protected function getMinimalDefinition(): array
    {
        return [
            'sku'   => 'ms-123',
            'name'  => [
                'en-GB' => 'T-Shirt',
            ],
            'price' => [
                // Wert in EUR
                'EUR' => ['regular' => 20],
            ],
            'tax'   => 19,
            'stock' => 1,
        ];
    }

    /**
     * Maximale Definition
     *
     * @return array
     */
    protected function getMaximalDefinition(): array
    {
        return [
            'sku'                 => 'ms-123',
            'name'                => ['en-GB' => 'T-Shirt', 'de-DE' => 'T-Shirt'],
            'description'         => ['en-GB' => 'Damn son where did you find this?', 'de-DE' => 'Durchaus ansehnlich.'],
            'price'               => [
                // Wert in EUR
                'EUR' => [
                    'regular' => 20,
                    'sale'    => 15,
                ],
            ],
            'tax'                 => 19,
            'stock'               => 1,
            'sales_channel'       => [
                // Muss kein Key sein
                'Storefront' => 'all',
                // ProductVisibilityDefinition::VISIBILITY_ALL
                'Headless'   => 'all',
            ],
            'ean'                 => '1234567891011',
            'release_date'        => '2038-01-19 00:00:00',
            'manufacturer_number' => '123-Test-ABC',
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
    protected function deleteProductBySku(string $sku): void
    {
        $productRepository = $this->getRepository('product.repository');
        $productEntity = $this->getProductBySku($sku);

        if (null !== $productEntity) {
            $productRepository->delete([
                [
                    'id' => $productEntity->getId(),
                ],
            ], $this->getContext());
        }
    }

    protected function getRepository(string $repository): EntityRepository
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getContainer()->get($repository);
    }

    protected function getProductBySku(string $sku): ProductEntity|null
    {
        $productRepository = $this->getRepository('product.repository');

        $criteria = new Criteria();
        $criteria->addAssociations(['visibilities', 'media', 'properties', 'translations', 'children', 'configuratorSettings', 'categories', 'manufacturer']);
        $criteria->addFilter(new EqualsFilter('productNumber', $sku));
        $productEntity = $productRepository->search($criteria, $this->getContext())->first();
        return $productEntity ?? null;
    }

    protected function getPropertyGroupByCode(string $propertyGroupCode): PropertyGroupEntity|null
    {
        $productRepository = $this->getRepository('property_group.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $propertyGroupCode));
        $propertyGroup = $productRepository->search($criteria, $this->getContext())->first();
        return $propertyGroup ?? null;
    }

    protected function getLayoutIdByType(string $type): CmsPageEntity|null
    {
        $cmsPageRepository = $this->getRepository('cms_page.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', $type));
        $cmsLayoutId = $cmsPageRepository->search($criteria, $this->getContext())->first();
        return $cmsLayoutId ?? null;
    }

    protected function getCustomFieldByCode(string $customFieldCode): CustomFieldEntity|null
    {
        $customFieldRepository = $this->getRepository('custom_field.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $customFieldCode));
        $customField = $customFieldRepository->search($criteria, $this->getContext())->first();
        return $customField ?? null;
    }

    protected function getPropertyGroupOptionByCode(string $propertyGroupOptionCode): PropertyGroupOptionEntity|null
    {
        $productRepository = $this->getRepository('property_group_option.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $propertyGroupOptionCode));
        $propertyGroupOption = $productRepository->search($criteria, $this->getContext())->first();
        return $propertyGroupOption ?? null;
    }

    protected function getTranslationIdByIsoCode(string $isoCode): string
    {
        $criteria = new Criteria();
        $criteria->addAssociations(['languages']);
        $criteria->addFilter(new EqualsFilter('code', $isoCode));
        $translationRepository = $this->getRepository('locale.repository');
        $translation = $translationRepository->search($criteria, $this->getContext())->first();

        return $translation->getLanguages()->first()->getUniqueIdentifier();
    }
}
