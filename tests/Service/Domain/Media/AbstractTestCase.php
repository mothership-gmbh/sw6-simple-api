<?php

namespace MothershipSimpleApiTests\Service\Domain\Media;

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


abstract class AbstractTestCase extends \MothershipSimpleApiTests\Service\AbstractTestCase
{
    /**
     * Minimale Definition, um ein Produkt anzulegen
     *
     * @return array
     */
    protected function getMinimalDefinition(): array
    {
        return [
            'url'             => 'https://via.placeholder.com/50x50.png',
            'mediaFolderName' => 'Category Media',
        ];
    }
}
