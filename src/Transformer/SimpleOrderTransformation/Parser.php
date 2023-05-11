<?php

namespace MothershipSimpleApi\Transformer\SimpleOrderTransformation;

/**
 * Bringt rohe Shopware-Bestelldaten in eine simplere Struktur.
 *
 * Setzt voraus, dass die Bestelldaten als $response in einem bestimmten Format übergeben werden.
 * Wir verwenden die Klasse derart, dass wir die $response direkt aus einer Anfrage an die SimpleApi (oder Shopware-API) erhalten.
 * Bei dieser API-Anfrage muss der Header 'Accept: * / *' (ohne Leerzeichen) gesetzt sein.
 * Durch diesen Header enthält die Response das Feld 'included', welches Informationen über assoziierte Entitäten
 * (z.B. order_customer, currency, order_address usw.) enthält.
 */
class Parser
{
    /**
     * @param array $response entspricht $response aus Shopware-API (Search-Order)
     *
     * @return array
     */
    public function parse(array $response): array
    {
        $container = [
            'invoiceNumber'          => '',
            'orderNumber'            => '',
            'orderNumberUUID'        => '',
            'webshopOrderStatus'     => '',
            'orderCurrencyCode'      => '',
            'createdAt'              => '',
            'taxAmount'              => 0,
            'shippingAmount'         => 0,
            'subTotal'               => 0,
            'grandTotal'             => 0,
            'shippingTotal'          => 0,
            'shippingTaxRate'        => 0,
            'discountName'           => '',
            'discountPercent'        => '',
            'discountAmount'         => 0,
            'discountedProducts'     => [
            ],
            'payment'                => [
                'type'         => '',
                'customFields' => [
                ],
            ],
            'shippingMethod'         => '',
            'shippingFlat'           => false,
            'customerId'             => '',
            'customerTitle'          => '',
            'customerEmail'          => '',
            'customerFirstname'      => '',
            'customerLastname'       => '',
            'billingAddress'         => [
                'firstname'   => '',
                'lastname'    => '',
                'street'      => '',
                'city'        => '',
                'company'     => '',
                'country'     => '',
                'phoneNumber' => '',
                'postCode'    => '',
                'region'      => '',
                'vatId'       => '',
                'id'          => '',
                'title'       => '',
            ],
            'shippingAddress'        => [
                'firstname'   => '',
                'lastname'    => '',
                'street'      => '',
                'city'        => '',
                'company'     => '',
                'country'     => '',
                'phoneNumber' => '',
                'postCode'    => '',
                'region'      => '',
                'vatId'       => '',
                'id'          => '',
                'title'       => '',
            ],
            'lineItemsGraph'         => [
            ],
            'lineItemsGraphOriginal' => [
            ],
        ];

        $this->setBaseAmounts($response, $container);
        $this->setOrderNumber($response, $container);
        $this->setInvoiceNumber($response, $container);
        $this->setOrderStatus($response, $container);
        $this->setCustomer($response, $container);
        $this->setCurrency($response, $container);
        $this->setPaymentDetails($response, $container);
        $this->setShipment($response, $container);
        $this->setLineItems($response, $container);
        $this->setAddress($response, $container);

        return $container;
    }

    private function setBaseAmounts(array $response, array &$container): void
    {
        $container['createdAt'] = $response['data'][0]['attributes']['orderDate'];
        if ($response['data'][0]['attributes']['price']['taxStatus'] === 'tax-free') {
            $container['subTotal'] = ($response['data'][0]['attributes']['amountNet'] * 1000 - ($response['data'][0]['attributes']['shippingCosts']['totalPrice'] * 1000)) / 1000;
            $container['shippingAmount'] = $response['data'][0]['attributes']['shippingCosts']['totalPrice'];
            $container['shippingTaxRate'] = 0;
            $container['taxAmount'] = 0;
        } else {
            $container['subTotal'] = ($response['data'][0]['attributes']['amountNet'] * 1000 - ($response['data'][0]['attributes']['shippingCosts']['totalPrice'] * 1000 - $response['data'][0]['attributes']['shippingCosts']['calculatedTaxes'][0]['tax'] * 1000)) / 1000;
            $container['shippingAmount'] = ($response['data'][0]['attributes']['shippingCosts']['totalPrice'] * 1000 - $response['data'][0]['attributes']['shippingCosts']['calculatedTaxes'][0]['tax'] * 1000) / 1000;
            $container['shippingTaxRate'] = $response['data'][0]['attributes']['shippingCosts']['calculatedTaxes'][0]['taxRate'];
            $container['taxAmount'] = $response['data'][0]['attributes']['price']['calculatedTaxes'][0]['tax'];
        }

        // GrandTotal = Brutto
        // FEHLT: TotalPaid PaymentAmount
        $container['grandTotal'] = $response['data'][0]['attributes']['amountTotal'];
        $container['shippingTotal'] = $response['data'][0]['attributes']['shippingTotal'];
    }

    /**
     * Setzt die essenziellen Kundendaten.
     *
     * @param array $response
     * @param array $container
     */
    private function setCustomer(array $response, array &$container): void
    {
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'order_customer') {
                $a = $data['attributes'];
                $container['customerTitle'] = $this->getSalutation($a['salutationId'], $response);
                $container['customerEmail'] = $a['email'];
                $container['customerFirstname'] = $a['firstName'];
                $container['customerLastname'] = $a['lastName'];
                $container['customerId'] = $a['customerNumber'];
            }
        }
    }

    /**
     * ToDo: Die Logik ist stark Aigner-abhängig. ist ok für jetzt, sollte in Zukunft durch die Salutation-Repository ersetzt werden, um die Übersetzten Titel zu erhalten.
     *
     * @param string $salutationId
     * @param array  $response
     * @param string $addressType
     *
     * @return string
     */
    private function getSalutation(
        string $salutationId,
        array $response,
        string $addressType = 'billing'
    ): string {
        $isoCodes = $this->getCountryIso($response);

        $customerTitle = 'Mr.';
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'salutation' && $data['id'] === $salutationId && '' !== $data['attributes']['displayName']) {
                $customerTitle = $data['attributes']['displayName'];
            }
        }

        $isoCode = $isoCodes[$addressType];
        $germanSalutation = ['DE', 'AT'];
        if (in_array($isoCode, $germanSalutation, true)) {
            switch ($customerTitle) {
                case 'Mr.':
                    $customerTitle = 'Herr';
                    break;
                case 'Mr. Dr.':
                    $customerTitle = 'Herr Dr.';
                    break;
                case 'Mrs.':
                    $customerTitle = 'Frau';
                    break;
                case 'Mrs. Dr.':
                    $customerTitle = 'Frau Dr.';
                    break;
            }
        }

        if ($customerTitle === 'Not specified') {
            $customerTitle = '';
        }

        return $customerTitle;
    }

    /**
     *
     * @param array $response
     * @param array $container
     */
    private function setOrderNumber(array $response, array &$container): void
    {
        $container['orderNumber'] = $response['data'][0]['attributes']['orderNumber'];
        $container['orderNumberUUID'] = $response['data'][0]['id'];
    }

    /**
     *
     * @param array $response
     * @param array $container
     */
    private function setInvoiceNumber(array $response, array &$container): void
    {
        foreach ($response['included'] as $data) {
            if (isset($data['attributes']['fileType'], $data['attributes']['config']['custom'])
                && ($data['type'] === 'document') && $data['attributes']['fileType'] === 'pdf'
                && $data['attributes']['config']['custom']['invoiceNumber']) {
                $container['invoiceNumber'] = $data['attributes']['config']['custom']['invoiceNumber'];
            }
        }
    }

    /**
     * @param array $response
     * @param array $container
     */
    private function setOrderStatus(array $response, array &$container): void
    {
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'state_machine_state') {
                $a = $data['attributes'];
                $container['webshopOrderStatus'] = $a['technicalName'];
            }
        }
    }

    /**
     *
     * @param array $response
     * @param array $container
     */
    private function setCurrency(array $response, array &$container): void
    {
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'currency') {
                $a = $data['attributes'];
                $container['orderCurrencyCode'] = $a['isoCode'];
            }
        }
    }

    /**
     *
     * @param array $response
     * @param array $container
     */
    private function setShipment(array $response, array &$container): void
    {
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'shipping_method') {
                $a = $data['attributes'];
                $container['shippingMethod'] = $a['name'];
            }
        }
    }

    /**
     *
     * @param array $response
     *
     * @return array
     */
    private function getCountryIso(array $response): array
    {
        $billingAddressId = $response['data'][0]['attributes']['billingAddressId'];
        $isoCodes = [
            'billing'  => null,
            'shipping' => null,
        ];
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'order_address') {
                $a = $data['attributes'];
                if ($data['id'] === $billingAddressId) {
                    foreach ($response['included'] as $countryData) {
                        if ($countryData['type'] === 'country') {
                            $b = $countryData['attributes'];
                            if ($countryData['id'] === $a['countryId']) {
                                $isoCodes['billing'] = $b['iso'];
                            }
                        }
                    }
                } else {
                    foreach ($response['included'] as $countryData) {
                        if ($countryData['type'] === 'country') {
                            $b = $countryData['attributes'];
                            if ($countryData['id'] === $a['countryId']) {
                                $isoCodes['shipping'] = $b['iso'];
                            }
                        }
                    }
                }
            }
        }

        return $isoCodes;
    }

    /**
     *
     * @param array $response
     * @param array $container
     */
    private function setAddress(array $response, array &$container): void
    {
        $billingAddressId = $response['data'][0]['attributes']['billingAddressId'];
        // Rechnungsadresse
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'order_address') {
                $a = $data['attributes'];

                if ($data['id'] === $billingAddressId) {
                    $container['billingAddress']['id'] = $data['id'];
                    $container['billingAddress']['firstname'] = $a['firstName'];
                    $container['billingAddress']['lastname'] = $a['lastName'];
                    $container['billingAddress']['street'] = $a['street'];
                    $container['billingAddress']['city'] = $a['city'];
                    $container['billingAddress']['company'] = $a['company'];
                    $container['billingAddress']['postCode'] = $a['zipcode'];
                    $container['billingAddress']['phoneNumber'] = $a['phoneNumber'];
                    $container['billingAddress']['country'] = null;
                    // falls Umsatzsteuer-ID vorhanden soll der Ländercode (die ersten 2 Buchstaben) entfernt werden
                    if ($a['vatId'] !== null) {
                        $vatIdSanitized = preg_replace('/\s/', '', $a['vatId']);
                        $container['billingAddress']['vatId'] = preg_replace('/^[a-z]{2}/i', '', $vatIdSanitized);
                    }

                    // Country
                    foreach ($response['included'] as $countryData) {
                        if ($countryData['type'] === 'country') {
                            $b = $countryData['attributes'];
                            if ($countryData['id'] === $a['countryId']) {
                                $container['billingAddress']['country'] = $b['iso'];
                            }
                        }
                    }
                } else {
                    $container['shippingAddress']['id'] = $data['id'];
                    $container['shippingAddress']['firstname'] = $a['firstName'];
                    $container['shippingAddress']['lastname'] = $a['lastName'];
                    $container['shippingAddress']['street'] = $a['street'];
                    $container['shippingAddress']['city'] = $a['city'];
                    $container['shippingAddress']['company'] = $a['company'];
                    $container['shippingAddress']['postCode'] = $a['zipcode'];
                    $container['shippingAddress']['phoneNumber'] = $a['phoneNumber'];
                    $container['shippingAddress']['title'] = $this->getSalutation(
                        $a['salutationId'],
                        $response,
                        'shipping'
                    );

                    // Country
                    foreach ($response['included'] as $countryData) {
                        if ($countryData['type'] === 'country') {
                            $b = $countryData['attributes'];
                            if ($countryData['id'] === $a['countryId']) {
                                $container['shippingAddress']['country'] = $b['iso'];
                            }
                        }
                    }
                }
            }
        }

        /*
         Möglicherweise kein schöner Workaround. Es muss jedoch immer eine ShippingAddress gesetzt werden.
         In dem Fall stellen wir das sicher, indem bei einer leeren ID die BillingAddressId benutzt wird.
         */
        if ($container['shippingAddress']['id'] === '') {
            $container['shippingAddress'] = $container['billingAddress'];
        }
    }

    /**
     * Die Payment-Informationen sind an zwei Stellen, einmal in der order_transaction und einmal getrennt im
     * payment_method. Wir benötigen zwingend beide Informationen.
     *
     * @param array $response
     * @param array $container
     */
    private function setPaymentDetails(array $response, array &$container): void
    {
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'payment_method') {
                $a = $data['attributes'];
                $container['payment']['type'] = $a['name'];
            }
        }

        foreach ($response['included'] as $data) {
            if ($data['type'] === 'order_transaction') {
                $a = $data['attributes'];
                /** @var $a array */
                if (array_key_exists('paymentMethodId', $a)) {
                    $container['payment']['customFields'] = $a['customFields'];
                }
            }
        }
    }

    /**
     * Die Line-Items in Shopware haben eine sehr interessante Struktur. Grundsätzlich liegen die Line-Items
     * immer in einer flachen Struktur vor. Beispiel:
     *
     * - order_line_item
     * - order_line_item
     * - order_line_item
     * - order_line_item
     *
     * Was nun nicht auf den ersten Blick ersichtlich wird, ist die Tatsache, dass es Hierarchien zwischen den einzelnen
     * Line-Items gibt. Im Grunde genommen nicht nur eine einfache Parent-Children-Beziehung, sondern durchaus mehrere
     * Verschachtelungen. Es handelt sich tatsächlich eher um einen gerichteten Graphen. Das Ergebnis könnte
     * wie folgt ausschauen:
     *
     * order_line_item
     *  --- order_line_item
     *  --- order_line_item
     *      --- order_line_item
     *  --- order_line_item
     *
     * @param array $response
     * @param array $container
     */
    private function setLineItems(array $response, array &$container): void
    {
        $discountedItems = [];

        $oderLineItems = [];
        foreach ($response['included'] as $data) {
            if ($data['type'] === 'order_line_item') {
                $oderLineItems[] = $data;
            }

            if (array_key_exists('type', $data['attributes']) && $data['attributes']['type'] === 'promotion') {
                // Die Discounts werden temporär gehalten.
                $discountContainer = [
                    'code'  => $data['attributes']['payload']['code'],
                    'value' => $data['attributes']['payload']['value'],
                    'type'  => $data['attributes']['payload']['discountType'],
                    'items' => $data['attributes']['payload']['composition'],
                ];

                $discountedItems[] = $discountContainer;
            }
        }

        // In dem Attribut befinden sich die unmodifizierten Daten.
        $graph = $this->rebuildHierarchy($oderLineItems, $discountedItems);
        $container['lineItemsGraphOriginal'] = $graph['original'];
        $container['lineItemsGraph'] = $graph['formatted'];
    }

    /**
     *
     * @param array $orderLine
     * @param array $discountedItems
     * @param float   $totalCartValue
     * @param float   $totalDiscountableCart
     * @param bool  $isDiscountAble
     *
     * @return array
     * @protected
     */
    private function createLineItem(
        array $orderLine,
        array $discountedItems,
        float $totalCartValue,
        float $totalDiscountableCart,
        bool $isDiscountAble = false
    ): array {
        $partToDiscount = 0;
        if ($isDiscountAble) {
            $partToDiscount = $orderLine['attributes']['price']['totalPrice'] / $totalDiscountableCart * 100;
            if ($partToDiscount > 100) {
                $partToDiscount = 100;
            }
        }
        $o = [
            'id'            => $orderLine['id'],
            // Anpassung nötig, weil es Testcases gibt, die keine productNumber haben.
            'productNumber' => $orderLine['attributes']['payload']['productNumber'] ?? null,
            'referenceId'   => $orderLine['attributes']['referencedId'],
            'identifier'    => $orderLine['attributes']['identifier'],
            'type'          => $orderLine['type'],
            'typeAttribute' => null,
            'price'         => [
                'quantity'                            => $orderLine['attributes']['price']['quantity'],
                'unitPrice'                           => $orderLine['attributes']['price']['unitPrice'],
                'totalPrice'                          => $orderLine['attributes']['price']['totalPrice'],
                'tax'                                 => 0,
                'taxRate'                             => 0,
                'partToTotalCartInPercentage'         => $orderLine['attributes']['price']['totalPrice'] / $totalCartValue * 100,
                'partToTotalCartDiscountInPercentage' => $partToDiscount,
            ],
            // Kann beliebige Werte enthalten
            'payload'       => $orderLine['attributes']['payload'],

            /*
             Bei einigen Kunden gibt es hier aus dem ERP merkwürdige Zeilenumbrüche, die wir entfernen.
             Es ist nicht klar, ob das an dieser Stelle die richtige Lösung ist, da hierdurch die Verantwortlichkeit
             auf die Transformation übertragen wird. Fehler sollten daher eher im Ausgangssystem gelöst werden.
             */
            'label'         => trim($orderLine['attributes']['label'], "\n\r"),
            'children'      => [],
        ];

        /*
         Nicht immer gibt es das Attribut "type". Wir brauchen diese Information aber für weitere Transformationen,
         falls sie vorhanden ist. Wichtig ist das insbesondere bei Line-Items mit verschiedenen Attributoptionen.
         Bei custom products wären das zum Beispiel 'customized-product-options' oder 'option-values'.
         */
        if (array_key_exists('type', $orderLine['attributes'])) {
            $o['typeAttribute'] = $orderLine['attributes']['type'];
        }
        if (count($orderLine['attributes']['price']['calculatedTaxes']) > 0) {
            $o['price']['tax'] = $orderLine['attributes']['price']['calculatedTaxes'][0]['tax'];
            $o['price']['taxRate'] = $orderLine['attributes']['price']['calculatedTaxes'][0]['taxRate'];
        }

        // Discounts werden in Shopware über eine DiscountComposition zur Verfügung gestellt.
        foreach ($discountedItems as $discountContainer) {
            foreach ($discountContainer['items'] as $discountComposition) {
                // (orderLine.attributes.productId === discountComposition.id) {
                if ($orderLine['attributes']['identifier'] === $discountComposition['id']) {
                    $o['discount'] = [
                        'type'        => $discountContainer['type'],
                        'type_amount' => $discountContainer['value'],
                        'code'        => $discountContainer['code'],
                        'value'       => $discountComposition['discount'],
                    ];
                }
            }
        }

        return $o;
    }

    /**
     * Kleine Abwandlung der Implementierung aus Stack-Overflow.
     *
     * Gibt zwei Objekte zurück, original und formatted. Original ist der
     *
     * @link https://stackoverflow.com/questions/18017869/build-tree-array-from-flat-array-in-javascript
     *
     * @param array $list
     * @param array $discountedItems
     *
     * @return array
     */
    private function rebuildHierarchy(array &$list, array $discountedItems): array
    {
        $map = [];
        $orderGraph = [];

        $orderGraphSimple = [];
        $listNew = [];

        /*
         Berechne den Gesamtwarenkorb-Wert OHNE Discount. Wir benötigen dies, um eine prozentuale
         Verteilung von Kaufgutscheinen an einzelne Position zu bestimmen.

         Wenn ich einen 100 EUR Kaufgutschein habe, wie viel % davon wird also auf eine bestimmte Position
         angewendet
         */
        $totalCartValue = 0;
        $totalDiscountableCart = 0;

        $discountableIds = [];
        foreach ($list as $orderLine) {
            if ($orderLine['attributes']['type'] === 'promotion') {
                foreach ($orderLine['attributes']['payload']['composition'] as $item) {
                    $discountableIds[] = $item['id'];
                }
            }
        }

        foreach ($list as $orderLine) {
            if ($orderLine['attributes']['type'] === 'product') {
                $totalCartValue += $orderLine['attributes']['price']['totalPrice'];
            }
            if (in_array($orderLine['attributes']['identifier'], $discountableIds, true)) {
                $totalDiscountableCart += $orderLine['attributes']['price']['totalPrice'];
            }
        }

        for ($i = 0, $iMax = count($list); $i < $iMax; ++$i) {
            $map[$list[$i]['id']] = $i;   // Map für Look-Up

            $list[$i]['children'] = [];

            $isDiscountAble = false;
            if (in_array($list[$i]['attributes']['identifier'], $discountableIds, true)) {
                $isDiscountAble = true;
            }
            $listNew[$i] = $this->createLineItem(
                $list[$i],
                $discountedItems,
                $totalCartValue,
                $totalDiscountableCart,
                $isDiscountAble
            );
        }

        for ($i = 0, $iMax = count($list); $i < $iMax; ++$i) {
            /*
             Hier werden mehrere Variablen explizit als Referenz übergeben.
             Das muss gemacht werden, weil zB $list[$i] später noch modifiziert wird und wir möchten, dass $node diese Veränderung in der referenzierten Variable ($list[$i]) mitbekommt.
             Dieser Ansatz ist etwas untypisch für PHP, weil alle Variablen außer Objekte hier standardmäßig per Pass-By-Value übergeben werden.
             {@link https://www.w3docs.com/snippets/php/how-to-pass-variables-by-reference-in-php.html}

             Hintergrund:
             Der Code wurde zunächst in TypeScript geschrieben und da ist Pass-By-Reference der Standard für Objekte & Arrays.
             {@link https://stackoverflow.com/a/13104500}
             */
            $node = &$list[$i];
            $nodeSimple = &$listNew[$i];

            if ($node['attributes']['parentId'] !== null) {
                $list[$map[$node['attributes']['parentId']]]['children'][] = &$node;
                $listNew[$map[$node['attributes']['parentId']]]['children'][] = &$nodeSimple;
            } else {
                $orderGraph[] = &$node;
                $orderGraphSimple[] = &$nodeSimple;
            }
        }

        return [
            'original'  => $orderGraph,
            'formatted' => $orderGraphSimple,
        ];
    }
}
