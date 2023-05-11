<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Definition\Request;
use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Processor\ActiveProcessor;
use MothershipSimpleApi\Service\Processor\CategoryProcessor;
use MothershipSimpleApi\Service\Processor\CustomFieldProcessor;
use MothershipSimpleApi\Service\Processor\ImageProcessor;
use MothershipSimpleApi\Service\Processor\LayoutProcessor;
use MothershipSimpleApi\Service\Processor\ManufacturerProcessor;
use MothershipSimpleApi\Service\Processor\PropertyGroupProcessor;
use MothershipSimpleApi\Service\Processor\TranslationProcessor;
use MothershipSimpleApi\Service\Processor\VariantProcessor;
use MothershipSimpleApi\Service\Processor\VisbilityProcessor;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class SimpleProductCreator
{
    public function __construct(
        protected EntityRepository $productRepository,
        protected EntityRepository $taxRepository,
        protected EntityRepository $currencyRepository,

        protected TranslationProcessor $translationProcessor,
        protected VisbilityProcessor $visbilityProcessor,
        protected ImageProcessor $imageProcessor,
        protected PropertyGroupProcessor $propertyGroupProcessor,
        protected CustomFieldProcessor $customFieldProcessor,
        protected VariantProcessor $variantProcessor,
        protected LayoutProcessor $layoutProcessor,
        protected ActiveProcessor $activeProcessor,
        protected CategoryProcessor $categoryProcessor,
        protected ManufacturerProcessor $manufacturerProcessor,

        protected Request $request,
    ) {
        $this->request                = $request;
    }

    public function createEntity(array $definition, Context $context)
    {
        $this->request->init($definition);
        $this->createProductByDefinition($definition, $context);
    }

    /**
     *
     * @link https://stackoverflow.com/questions/74450074/shopware-6-how-create-a-product-with-media-with-the-admin-api
     * @link https://www.matheusgontijo.com/2022/01/28/how-to-create-a-product-programmatically-in-shopware-6
     *
     * @param array   $definition
     * @param Context $context
     *
     * @return void
     * @throws InvalidTaxValueException
     */
    protected function createProductByDefinition(array $definition, Context $context)
    {
        try {
            foreach ($this->request->getVariants() as $variant) {
                $this->upsertProduct($variant, $context);
            }
            $this->upsertProduct($this->request->getProduct(), $context);

            // Ab Hier VariantenHandling
            $this->variantProcessor->process($this->request, $context);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function upsertProduct(Product $product, Context $context)
    {
        $productUuid = Uuid::fromStringToHex($product->getSku());
        $data = [
            'id'            => $productUuid,
            'price'         => $this->setPrice($product->getPrice(), $product->getTax(), $context),
            'taxId'         => $this->setTax($product->getTax(), $context),
            'stock'         => $product->getStock(),
            'productNumber' => $product->getSku()
        ];

        $this->translationProcessor->process($data, $product);
        $this->layoutProcessor->process($data, $product);
        $this->activeProcessor->process($data, $product);
        $this->manufacturerProcessor->process($data, $product, $context);

        // Für die Zuordnung der Kategorien
        $this->categoryProcessor->process($data, $product, $productUuid, $context);

        // Für die Zuordnung des Sales-Channel
        $this->visbilityProcessor->process($data, $product, $productUuid, $context);
        $this->productRepository->upsert([$data], $context);

        // Kann erst durchgeführt werden, nachdem es Produkte gibt.
        $imageUpdated = $this->imageProcessor->process($product, $productUuid, $context);
        if (!empty($imageUpdated)) {
            $this->productRepository->update([$imageUpdated], $context);
        }

        $this->propertyGroupProcessor->process($product, $productUuid, $context);
        $customFieldData = [
            'id' => $productUuid
        ];
        $this->customFieldProcessor->process($customFieldData, $product, $productUuid, $context);
        $this->productRepository->update([$customFieldData], $context);
    }

    /**
     * Die Steuer-ID ist in der Tabelle 'tax' enthalten. Hierzu soll nach der Steuer gesucht werden.
     *
     * @param float   $taxRate
     * @param Context $context
     *
     * @return string
     * @throws InvalidTaxValueException
     */
    protected function setTax(float $taxRate, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));

        $taxId = $this->taxRepository->searchIds($criteria, $context)->firstId();
        if (null == $taxId) {
            throw new InvalidTaxValueException('There is no tax with a value of [' . $taxRate . '] in the table tax');
        }
        return $taxId;
    }

    protected function setPrice(array $prices, float $taxRate, Context $context) : array
    {
        $data = [];
        foreach ($prices as $currencyIsoCode => $grossPrice) {
            $currencyId = $this->getCurrencyIdByIsoCode($currencyIsoCode, $context);
            $data[] = [
                'currencyId' => $currencyId,
                'gross'      => $grossPrice,
                'net'        => $grossPrice / (100 + $taxRate) * 100,
                'linked'     => true,
            ];
        }
        return $data;
    }

    protected function getCurrencyIdByIsoCode(string $currencyIsoCode, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $currencyIsoCode));

        $currencyId = $this->currencyRepository->searchIds($criteria, $context)->firstId();
        if (null == $currencyId) {
            throw new InvalidCurrencyCodeException('There is no currency [' . $currencyIsoCode . '] in the table currency');
        }
        return $currencyId;
    }
}
