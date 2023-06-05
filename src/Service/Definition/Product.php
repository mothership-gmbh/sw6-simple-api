<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Definition;

class Product
{
    protected array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function initWithData(array $data): Product
    {
        return new self($data);
    }

    public function getImages()
    {
        return $this->getPropertyByKey('images');
    }

    public function getSku()
    {
        return $this->getPropertyByKey('sku');
    }

    public function getName()
    {
        return $this->getPropertyByKey('name');
    }

    public function getDescription()
    {
        return $this->getPropertyByKey('description');
    }

    public function getKeywords()
    {
        return $this->getPropertyByKey('keywords');
    }

    public function getMeta_title()
    {
        return $this->getPropertyByKey('meta_title');
    }

    public function getMeta_description()
    {
        return $this->getPropertyByKey('meta_description');
    }

    public function getPrice()
    {
        return $this->getPropertyByKey('price');
    }

    public function getCategories()
    {
        return $this->getPropertyByKey('categories');
    }

    public function getSalesChannel()
    {
        return $this->getPropertyByKey('sales_channel');
    }

    public function getStock()
    {
        return $this->getPropertyByKey('stock');
    }

    public function getTax()
    {
        return $this->getPropertyByKey('tax');
    }

    public function getVariants()
    {
        return $this->getPropertyByKey('variants');
    }

    public function getCmsPageId()
    {
        return $this->getPropertyByKey('cms_page_id');
    }

    public function getActive()
    {
        return $this->getPropertyByKey('active');
    }

    public function getManufacturer()
    {
        return $this->getPropertyByKey('manufacturer');
    }

    public function getEan()
    {
        return $this->getPropertyByKey('ean');
    }

    public function getReleaseDate()
    {
        return $this->getPropertyByKey('release_date');
    }

    public function getManufacturerNumber()
    {
        return $this->getPropertyByKey('manufacturer_number');
    }

    public function getAxis(): array
    {
        $axis = $this->getPropertyByKey('axis');
        return $axis ?? [];
    }

    /**
     * Nicht zu verwechseln mit der Methode "getPropertyByKey". Es ist rein zufällig,
     * da die Shopware-Terminologie für Produkt-Attribute ebenfalls "property" ist.
     *
     * @return mixed
     */
    public function getProperties(): array
    {
        $properties = $this->getPropertyByKey('properties');
        return $properties ?? [];
    }

    public function getCustomFields(): array
    {
        $customFields = $this->getPropertyByKey('custom_fields');
        return $customFields ?? [];
    }
    protected function getPropertyByKey(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}
