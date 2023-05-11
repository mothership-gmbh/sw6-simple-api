<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Definition;

use MothershipSimpleApi\Service\Validator\ActiveValidator;
use MothershipSimpleApi\Service\Validator\CategoryValidator;
use MothershipSimpleApi\Service\Validator\CmsPageIdValidator;
use MothershipSimpleApi\Service\Validator\CustomFieldValidator;
use MothershipSimpleApi\Service\Validator\ImageValidator;
use MothershipSimpleApi\Service\Validator\ManufacturerValidator;
use MothershipSimpleApi\Service\Validator\PriceValidator;
use MothershipSimpleApi\Service\Validator\PropertyValidator;
use MothershipSimpleApi\Service\Validator\SalesChannelValidator;
use MothershipSimpleApi\Service\Validator\SkuValidator;
use MothershipSimpleApi\Service\Validator\StockValidator;
use MothershipSimpleApi\Service\Validator\TaxValidator;
use MothershipSimpleApi\Service\Validator\TranslationValidator;

class Request
{
    protected array $request = [];

    protected Product $product;

    /* @var $variants Product[] */
    protected array $variants = [];

    public function __construct(
        protected PriceValidator        $priceValidator,
        protected SkuValidator          $skuValidator,
        protected TaxValidator          $taxValidator,
        protected StockValidator        $stockValidator,
        protected SalesChannelValidator $salesChannelValidator,
        protected ImageValidator        $imageValidator,
        protected PropertyValidator     $propertyValidator,
        protected CustomFieldValidator  $customFieldValidator,
        protected TranslationValidator  $translationValidator,
        protected CmsPageIdValidator    $cmsPageIdValidator,
        protected ActiveValidator       $activeValidator,
        protected CategoryValidator     $categoryValidator,
        protected ManufacturerValidator $manufacturerValidator
    )
    {
    }

    public function init(array $request): void
    {
        $this->request = $request;
        $product = Product::initWithData($request);
        $this->validate($product);
        $this->product = $product;

        $this->variants = [];
        if (array_key_exists('variants', $request)) {
            foreach ($request['variants'] as $variant) {
                $variantProduct = Product::initWithData($variant);
                $this->validate($variantProduct);
                $this->variants[] = $variantProduct;
            }
        }
    }

    /**
     * Die Definition wird immer vorab durch eine Reihe von Validatoren geprüft, um die
     * Konsistenz der Struktur zu prüfen.
     *
     * @param Product $product
     *
     * @return void
     */
    protected function validate(Product $product): void
    {
        $registeredValidator = [
            $this->priceValidator,
            $this->skuValidator,
            $this->taxValidator,
            $this->stockValidator,
            $this->salesChannelValidator,
            $this->imageValidator,
            $this->propertyValidator,
            $this->customFieldValidator,
            $this->translationValidator,
            $this->cmsPageIdValidator,
            $this->activeValidator,
            $this->categoryValidator,
            $this->manufacturerValidator,
        ];
        foreach ($registeredValidator as $validator) {
            $validator->validate($product);
        }
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return array []Product
     */
    public function getVariants(): array
    {
        return $this->variants;
    }
}
