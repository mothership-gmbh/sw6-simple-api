<?php

namespace MothershipSimpleApi\Transformer\SimpleOrderTransformation;

class Transformer
{
    protected Parser $parser;

    protected array $shopwareOrder;

    public function __construct()
    {
        $this->parser = new Parser;
    }

    public function init(array $shopwareOrder): void
    {
        $this->shopwareOrder = $shopwareOrder;
    }


    /**
     * @return array    konvertierte SimpleOrder
     */
    public function map(): array
    {
        return $this->parser->parse($this->shopwareOrder);
    }
}
