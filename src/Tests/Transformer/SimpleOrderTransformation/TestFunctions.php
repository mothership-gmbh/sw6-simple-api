<?php

namespace MothershipSimpleApi\Tests\Transformer\SimpleOrderTransformation;

use PHPUnit\Framework\Assert;

class TestFunctions
{
    /**
     * Prüft die Anzahl der Line-Items
     *
     * @param int   $count
     * @param array $items
     */
    public function assertExpectedNumberOfItems(int $count, array $items): void
    {
        Assert::assertCount($count, $items);
    }

    /**
     *
     * @param string $paymentType
     * @param array  $response
     */
    public function assertPayment(string $paymentType, array $response): void
    {
        Assert::assertEquals($response["payment"]["type"], $paymentType);
    }

    /**
     *
     * @param string $paymentType
     * @param array  $response
     */
    public function assertShipment(string $paymentType, array $response): void
    {
        Assert::assertEquals($response["shippingMethod"], $paymentType);
    }

    /**
     * Versand- und Rechnungsadresse sind identisch.
     *
     * @param array response
     */
    public function assertAddressEqual(array $response): void
    {
        Assert::assertEquals($response["shippingAddress"]["id"], $response["billingAddress"]["id"]);
    }

    /**
     * Versand- und Rechnungsadresse sind abweichend.
     *
     * @param array response
     */
    public function assertAddressNotEqual(array $response): void
    {
        Assert::assertNotEquals($response["shippingAddress"]["id"], $response["billingAddress"]["id"]);
    }

    public function assertStructure(array $response): void
    {
        Assert::assertArrayHasKey("orderNumber", $response);
        Assert::assertArrayHasKey("webshopOrderStatus", $response);
        Assert::assertArrayHasKey("orderCurrencyCode", $response);
        Assert::assertArrayHasKey("createdAt", $response);
        Assert::assertArrayHasKey("taxAmount", $response);
        Assert::assertArrayHasKey("shippingAmount", $response);
        Assert::assertArrayHasKey("shippingTaxRate", $response);
        Assert::assertArrayHasKey("subTotal", $response);
        Assert::assertArrayHasKey("grandTotal", $response);
        Assert::assertArrayHasKey("shippingTotal", $response);
        Assert::assertArrayHasKey("discountAmount", $response);
        Assert::assertArrayHasKey("discountName", $response);
        Assert::assertArrayHasKey("discountPercent", $response);
        Assert::assertArrayHasKey("discountedProducts", $response);
        Assert::assertArrayHasKey("shippingMethod", $response);
        Assert::assertArrayHasKey("shippingFlat", $response);
        Assert::assertArrayHasKey("customerEmail", $response);
        Assert::assertArrayHasKey("customerFirstname", $response);
        Assert::assertArrayHasKey("customerLastname", $response);
        Assert::assertArrayHasKey("billingAddress", $response);
        Assert::assertArrayHasKey("shippingAddress", $response);
        Assert::assertArrayHasKey("payment", $response);

        // Plausibilitätscheck
        $this->assertValidGrandTotal($response);
    }

    /**
     * Assert-Helper für die in Shopware definierten Versandarten.
     *
     * @param array response
     */
    public function assertValidShippingMethod(array $response): void
    {
        $validShippingMethods = [
            'Versand Region F',
            'Versand Region B',
            'Versand Region D',
            'Versand Region T',
            'Versand Region S',
            'Versand Region W',
            'Versand Region E',
            'Versand Region A',
            'Versand Region X',
            'Versand Region V',
            'Versand Region C',
            'Versand Region R',
            'Versand Region U',
            'Versand DE',
            'Versand DE - flachliegend',
            'Versand AT',
            'Versand AT - flachliegend',
            'Standardversand - Region A',
            'Standardversand - Region B',
            'Standardversand - Region C',
            'Standardversand - Region V',
            'Standardversand - Region S',
            // nur im Teststadium valide
            'Versandmatrix',
            'Test',
        ];
        Assert::assertContains($response["shippingMethod"], $validShippingMethods);
    }

    /**
     * Assert-Helper für den Brutto-Preis. Es kommt immer wieder zu Rundungsfehler in Javascript,
     * weswegen mit Math.round gerundet wird.
     *
     * @param array response
     */
    public function assertValidGrandTotal(array $response): void
    {
        $calculated = round(($response["taxAmount"] + $response["subTotal"] + $response["shippingAmount"]) * 100) / 100;
        Assert::assertEquals($calculated, $response["grandTotal"]);
    }

    /**
     * Assert-Helper für die Währung.
     * Aktuell hardgecodet auf EUR.
     *
     * @param array response
     */
    public function assertValidOrderCurrencyCode(array $response): void
    {
        Assert::assertEquals($response["orderCurrencyCode"], 'EUR');
    }

    public function assertExpectedShippingCosts(float $shippingTotal, int $shippingTaxRate, array $repsonse): void
    {
        Assert::assertEquals($repsonse["shippingTotal"], $shippingTotal);
        Assert::assertEquals($repsonse["shippingTaxRate"], $shippingTaxRate);
    }


    /**
     *
     * @param array  $item
     * @param string $type
     * @param string $typeAmount
     * @param float    $value
     */
    public function assertDiscount(array $item, string $type, string $typeAmount, float $value): void
    {
        Assert::assertEquals($item["discount"]["type"], $type);
    }

    /**
     * Assert-Helper für die Mehrwertsteuer.
     * Aktuell hardgecodet auf 19%.
     *
     * @param array $response
     */
    public function assertValidTaxRate(array $response): void
    {
        foreach ($response["lineItemsGraph"] as $a) {
            Assert::assertEquals($a["taxPercent"], 19);
        }
    }

    /**
     * Assert-Helper für die Mehrwertsteuer.
     * Aktuell hardgecodet auf 19%.
     *
     * @param array $response
     */
    public function assertValidVatId(array $response): void
    {
        Assert::assertMatchesRegularExpression('/^$|[0-9]/', $response["billingAddress"]["vatId"]);
    }

    /**
     * führt alle relevanten Assertions aus
     *
     * @param array $response
     */
    public function assertRelevant(array $response): void
    {
        $this->assertValidOrderCurrencyCode($response);
        $this->assertValidShippingMethod($response);
        $this->assertValidGrandTotal($response);
    }
}
