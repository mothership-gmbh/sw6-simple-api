<?php

namespace MothershipSimpleApiTests\Service;

use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\SimpleProductCreator;
use MothershipSimpleApi\Service\SimpleProductSender;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class SimpleProductSenderTest extends AbstractTestCase
{
    protected SimpleProductSender $simpleProductSender;
    protected EntityRepository $enqueueRepository;


    protected function setUp(): void
    {
        $this->simpleProductSender = $this->getContainer()->get(SimpleProductSender::class);
        $this->enqueueRepository = $this->getContainer()->get('enqueue.repository');

        $this->cleanQueue();

       # $product = $this->getMinimalDefinition();
       # $this->deleteProductBySku($product['sku']);
    }

    /**
     * Übergibt ein Produkt an den MessageBus
     *
     * @test
     *
     * @group SimpleApi
     * @group SimpleApi_Product
     * @group SimpleApi_Product_Sender
     * @group SimpleApi_Product_Sender_1
     * @throws InvalidCurrencyCodeException
     * @throws InvalidSalesChannelNameException
     * @throws InvalidTaxValueException
     */
    public function createBasicProduct(): void
    {
        $message = [];

        $this->simpleProductSender->sendMessage($message);


        return;
        $productDefinition = $this->getMinimalDefinition();
        $context = $this->getContext();
        $this->simpleProductCreator->createEntity($productDefinition, $context);

        $createdProduct = $this->getProductBySku($productDefinition['sku']);

        $this->assertInstanceOf(ProductEntity::class, $createdProduct);
        $this->assertEquals($productDefinition['sku'], $createdProduct->getProductNumber());
        $this->assertEquals($productDefinition['tax'], $createdProduct->getTax()->getTaxRate());
        $this->assertEquals($productDefinition['price']['EUR']['regular'], $createdProduct->getPrice()->first()->getGross());
        $this->assertEquals($productDefinition['stock'], $createdProduct->getStock());
        $this->assertEquals($productDefinition['name']['en-GB'], $createdProduct->getName());
    }

    protected function cleanQueue(): void
    {
        /* @var EntityRepository $repository */
        $repository = $this->getContainer()->get('enqueue.repository');

        $criteria = new Criteria();
        foreach ($repository?->search($criteria, $this->getContext())->getElements() as $element) {
            /** @var ProductEntity $element */
            try {
                $repository?->delete([['id' => $element->getId()]], $this->getContext());
            } catch (\Exception) {
                // Es soll einfach versucht werden, alles zu löschen.
            }
        }
    }
}
