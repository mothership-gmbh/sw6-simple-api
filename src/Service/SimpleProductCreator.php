<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service;

use MothershipSimpleApi\Service\Definition\Product;
use MothershipSimpleApi\Service\Definition\Product\Request;
use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\Processor\ActiveProcessor;
use MothershipSimpleApi\Service\Processor\CategoryProcessor;
use MothershipSimpleApi\Service\Processor\CustomFieldProcessor;
use MothershipSimpleApi\Service\Processor\ImageProcessor;
use MothershipSimpleApi\Service\Processor\LayoutProcessor;
use MothershipSimpleApi\Service\Processor\EanProcessor;
use MothershipSimpleApi\Service\Processor\ReleaseDateProcessor;
use MothershipSimpleApi\Service\Processor\ManufacturerNumberProcessor;
use MothershipSimpleApi\Service\Processor\ManufacturerProcessor;
use MothershipSimpleApi\Service\Processor\PropertyGroupProcessor;
use MothershipSimpleApi\Service\Processor\TranslationProcessor;
use MothershipSimpleApi\Service\Processor\VariantProcessor;
use MothershipSimpleApi\Service\Processor\VisibilityProcessor;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class SimpleProductCreator
{
    public function __construct(
        protected EntityRepository              $productRepository,
        protected EntityRepository              $taxRepository,
        protected EntityRepository              $currencyRepository,
        protected TranslationProcessor          $translationProcessor,
        protected VisibilityProcessor           $visibilityProcessor,
        protected ImageProcessor                $imageProcessor,
        protected PropertyGroupProcessor        $propertyGroupProcessor,
        protected CustomFieldProcessor          $customFieldProcessor,
        protected VariantProcessor              $variantProcessor,
        protected LayoutProcessor               $layoutProcessor,
        protected EanProcessor                  $eanProcessor,
        protected ReleaseDateProcessor          $releaseDateProcessor,
        protected ManufacturerNumberProcessor   $manufacturerNumberProcessor,
        protected ActiveProcessor               $activeProcessor,
        protected CategoryProcessor             $categoryProcessor,
        protected ManufacturerProcessor         $manufacturerProcessor,
        protected Request $request,
    )
    {
    }

    /**
     * @param array   $definition
     * @param Context $context
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function createEntity(array $definition, Context $context): void
    {
        $this->request->init($definition);
        $this->createProductByDefinition($context);
    }

    /**
     *
     * @link https://stackoverflow.com/questions/74450074/shopware-6-how-create-a-product-with-media-with-the-admin-api
     * @link https://www.matheusgontijo.com/2022/01/28/how-to-create-a-product-programmatically-in-shopware-6
     *
     * @param Context $context
     *
     * @return void
     * @throws InvalidCurrencyCodeException
     * @throws InvalidTaxValueException
     * @throws InvalidSalesChannelNameException
     */
    protected function createProductByDefinition(Context $context): void
    {
        foreach ($this->request->getVariants() as $variant) {
            $this->upsertProduct($variant, $context);
        }
        $this->upsertProduct($this->request->getProduct(), $context);

        // Ab Hier VariantenHandling
        $this->variantProcessor->process($this->request, $context);
    }

    /**
     * @param Product $product
     * @param Context $context
     *
     * @throws InvalidCurrencyCodeException
     * @throws InvalidTaxValueException
     * @throws InvalidSalesChannelNameException
     */
    protected function upsertProduct(Product $product, Context $context): void
    {
        $productUuid = Uuid::fromStringToHex($product->getSku());
        $data = [
            'id'            => $productUuid,
            'price'         => $this->setPrice($product->getPrice(), $product->getTax(), $context),
            'taxId'         => $this->setTax($product->getTax(), $context),
            'stock'         => $product->getStock(),
            'productNumber' => $product->getSku(),
        ];

        $this->translationProcessor->process($data, $product);
        $this->layoutProcessor->process($data, $product);
        $this->eanProcessor->process($data, $product);
        $this->releaseDateProcessor->process($data, $product);
        $this->manufacturerNumberProcessor->process($data, $product);

        $this->activeProcessor->process($data, $product);
        $this->manufacturerProcessor->process($data, $product, $context);

        // Für die Zuordnung der Kategorien
        $this->categoryProcessor->process($data, $product, $productUuid, $context);

        // Für die Zuordnung des Sales-Channels
        $this->visibilityProcessor->process($data, $product, $productUuid, $context);
        $this->productRepository->upsert([$data], $context);

        // Kann erst durchgeführt werden, nachdem es Produkte gibt
        $imageUpdated = $this->imageProcessor->process($product, $productUuid, $context);
        if (!empty($imageUpdated)) {
            $this->productRepository->update([$imageUpdated], $context);
        }

        $this->propertyGroupProcessor->process($product, $productUuid, $context);
        $customFieldData = [
            'id' => $productUuid,
        ];
        $this->customFieldProcessor->process($customFieldData, $product, $context);
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
    protected function setTax(float $taxRate, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));

        $taxId = $this->taxRepository->searchIds($criteria, $context)->firstId();
        if (null === $taxId) {
            throw new InvalidTaxValueException('There is no tax with a value of [' . $taxRate . '] in the table tax');
        }
        return $taxId;
    }

    /**
     * @throws InvalidCurrencyCodeException
     */
    protected function setPrice(array $prices, float $taxRate, Context $context): array
    {
        $data = [];
        foreach ($prices as $currencyIsoCode => $price) {
            $currencyId = $this->getCurrencyIdByIsoCode($currencyIsoCode, $context);
            if (array_key_exists('sale', $price)) {
                $data[] = $this->setSalePrice($taxRate, $currencyId, $price['regular'], $price['sale']);
            } else {
                $data[] = [
                    'currencyId' => $currencyId,
                    'gross'      => $price['regular'],
                    'net'        => $price['regular'] / (100 + $taxRate) * 100,
                    'linked'     => true,
                ];
            }
        }
        return $data;
    }

    protected function setSalePrice(float $taxRate, string $currencyId, float $priceGross, float $salePriceGross = 0.00): array
    {
        $salePriceNet = $salePriceGross / (100 + $taxRate) * 100;
        $priceNet        = $priceGross / (100 + $taxRate) * 100;

        return [
            'currencyId' => $currencyId,
            'gross'      => $salePriceGross,
            'net'        => $salePriceNet,
            'linked'     => true,
            // wird dann als durchgestrichener "Streichpreis" angezeigt
            'listPrice'  => [
                'currencyId' => $currencyId,
                'gross'      => $priceGross,
                'net'        => $priceNet,
                'linked'     => true,
            ]
        ];
    }

    /**
     * @throws InvalidCurrencyCodeException
     */
    protected function getCurrencyIdByIsoCode(string $currencyIsoCode, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $currencyIsoCode));

        $currencyId = $this->currencyRepository->searchIds($criteria, $context)->firstId();
        if (null === $currencyId) {
            throw new InvalidCurrencyCodeException('There is no currency [' . $currencyIsoCode . '] in the table currency');
        }
        return $currencyId;
    }
}
