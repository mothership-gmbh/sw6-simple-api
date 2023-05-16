<?php

namespace MothershipSimpleApiTests\Transformer\SimpleOrderTransformation;

use JsonException;
use MothershipSimpleApi\Transformer\SimpleOrderTransformation\Transformer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    protected TestFunctions $testHelper;
    protected string $fixtureDir;
    protected Transformer $t;

    /**
     * Generischer Test, um die grundlegende Struktur aller Bestellungen zu prüfen.
     * Achtung: Ersetzt keine spezi
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_0
     * @throws JsonException
     */
    public function checkBasicStructures(): void
    {
        $fixtures = scandir($this->fixtureDir);
        foreach ($fixtures as $fixture) {
            if (is_dir($fixture)) {
                continue;
            }
            $simpleResponse = $this->getSimpleResponseFromFixture($fixture);
            $this->testHelper->assertStructure($simpleResponse);
        }
    }

    /**
     * @throws JsonException
     */
    protected function getSimpleResponseFromFixture(string $fixture): array
    {
        $response = json_decode(file_get_contents($this->fixtureDir . $fixture), true, 512, JSON_THROW_ON_ERROR);
        $this->t->init($response);

        return $this->t->map();
    }

    // Simple-Order-Transformation

    /**
     * Artikel:         Normal(28)
     * Versandart:      Standard
     * Bezahlart:       PayPal
     * Lieferadresse:   Abweichend
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_1
     * @throws JsonException
     */
    public function N28_ShippingRegionT_PayPal_AddressEqual_2(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('order_100041_it_paypal_a4.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(28, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressEqual($simpleResponse);
        $this->testHelper->assertPayment('PayPal', $simpleResponse);
        $this->testHelper->assertShipment('Versand Region T', $simpleResponse);
        $this->testHelper->assertExpectedShippingCosts(17.9, 19, $simpleResponse);
    }

    /**
     * Artikel:         Normal(4)
     * Versandart:      Standard
     * Bezahlart:       PayPal
     * Lieferadresse:   Abweichend
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_2
     * @throws JsonException
     */
    public function N4_Standard_PayPal_AddressEqual(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('10020_de_nys.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(4, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressEqual($simpleResponse);
        $this->testHelper->assertPayment('Paid in advance', $simpleResponse);
        $this->testHelper->assertShipment('Standard', $simpleResponse);
    }

    /**
     * Artikel:         Normal(5)
     * Versandart:      Standard
     * Bezahlart:       PayPal
     * Lieferadresse:   Abweichend
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_3
     * @throws JsonException
     */
    public function N5_PayPal_VersandRegionW_AddressEqual(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('order_100082_ch_paypal_discount.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(5, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressEqual($simpleResponse);
        $this->testHelper->assertPayment('PayPal', $simpleResponse);
        $this->testHelper->assertShipment('Versand Region W', $simpleResponse);
    }

    /**
     * Artikel:         Custom(1)
     * Versandart:      Standard
     * Bezahlart:       PayPal
     * Lieferadresse:   Abweichend
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_4
     * @throws JsonException
     */
    public function C1_Standard_PaidInAdvance_AddressEqual(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('10027_de_nys_aigner_logo_and_heart.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(1, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressEqual($simpleResponse);
        $this->testHelper->assertPayment('Paid in advance', $simpleResponse);
        $this->testHelper->assertShipment('Standard', $simpleResponse);
    }

    /**
     * Artikel:         Normal(1)
     * Versandart:      Standard
     * Bezahlart:       PayPal
     * Lieferadresse:   Abweichend
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_5
     * @throws JsonException
     */
    public function N1_Standard_PayPal_AddressNotEqual(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('10036_de_paypal_normal_items.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(1, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressNotEqual($simpleResponse);
        $this->testHelper->assertPayment('PayPal', $simpleResponse);
        $this->testHelper->assertShipment('Standard', $simpleResponse);
    }

    /**
     * Artikel:         Normal(1), Custom(1)
     * Versandart:      Standard
     * Bezahlart:       Vorkasse
     * Lieferadresse:   Identisch
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_6
     * @throws JsonException
     */
    public function N1C1_Standard_Prepaid_AddressEqual(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('10037_de_vorkasse_mixed_items.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(2, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressEqual($simpleResponse);
        $this->testHelper->assertPayment('Paid in advance', $simpleResponse);
        $this->testHelper->assertShipment('Standard', $simpleResponse);
    }

    /**
     * Eigentlich ist es ein Kaufgutschein. Es wird jedoch gehandhabt wie ein ganz normales Produkt,
     * da es technisch gesehen ein ganz normales Produkt ist.
     *
     * Artikel:         Normal(1)
     * Versandart:      Standard
     * Bezahlart:       PayPal
     * Lieferadresse:   Identisch
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_7
     * @throws JsonException
     */
    public function N1_Standard_PayPal_AddressEqual(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('aigner_10047_de_paypal_kaufgutschein.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(1, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressEqual($simpleResponse);
        $this->testHelper->assertPayment('PayPal', $simpleResponse);
        $this->testHelper->assertShipment('Standard', $simpleResponse);
    }

    /**
     * Eigentlich ist es ein Kaufgutschein. Es wird jedoch gehandhabt wie ein ganz normales Produkt,
     * da es technisch gesehen ein ganz normales Produkt ist.
     *
     * Artikel:         Normal(1)
     * Discount:        Coupon(1), PriceRule(1)
     * Versandart:      Standard
     * Bezahlart:       PayPal
     * Lieferadresse:   Identisch
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_8
     * @throws JsonException
     */
    public function N2_Coupon1_PriceRule1_Standard_PayPal_AddressEqual(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('aigner_10051_de_paypal_gutschein_preisregel_normal_items.json');

        $this->testHelper->assertStructure($simpleResponse);
        $this->testHelper->assertExpectedNumberOfItems(4, $simpleResponse['lineItemsGraph']);
        $this->testHelper->assertAddressEqual($simpleResponse);
        $this->testHelper->assertPayment('PayPal', $simpleResponse);
        $this->testHelper->assertShipment('Standard', $simpleResponse);

        // Discount checken
        $this->testHelper->assertDiscount($simpleResponse['lineItemsGraph'][0], 'percentage');
        $this->testHelper->assertDiscount($simpleResponse['lineItemsGraph'][3], 'percentage');
    }

    /**
     * Wichtiger Test
     *
     * Prüft, dass die Rechnungsnummer vorhanden ist.
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_9
     * @throws JsonException
     */
    public function Bestellung_mit_Rechnungsinformation(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('aigner_10088_de_bestellung_mit_rechnung.json');

        $this->testHelper->assertStructure($simpleResponse);
        // Rechnungsnummer gesetzt.
        Assert::assertEquals('1014', $simpleResponse['invoiceNumber']);
    }

    /**
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_10
     * @throws JsonException
     */
    public function Bestellung_mit_Zahlart_Free(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('aigner_10122_de_free.json');

        $this->testHelper->assertStructure($simpleResponse);
        Assert::assertEquals('Free of charge', $simpleResponse['payment']['type']);
    }

    /**
     * Wichtiger Test
     *
     * Prüft, dass eine Anrede vorhanden ist.
     *
     * @test
     *
     * @group SimpleOrderTransformation
     * @group SimpleOrderTransformation_11
     * @throws JsonException
     */
    public function Bestellung_mit_Anrede(): void
    {
        $simpleResponse = $this->getSimpleResponseFromFixture('test.json');

        $this->testHelper->assertStructure($simpleResponse);
        Assert::assertEquals('Herr', $simpleResponse['customerTitle']);
    }

    protected function setUp(): void
    {
        $this->testHelper = new TestFunctions();
        $this->fixtureDir = __DIR__ . '/fixtures/';
        $this->t = new Transformer();
    }
}
