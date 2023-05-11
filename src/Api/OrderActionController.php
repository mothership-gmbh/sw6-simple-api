<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Api;

use JsonException;
use MothershipSimpleApi\Api\Controller\AbstractApiController;
use MothershipSimpleApi\Transformer\SimpleOrderTransformation\Transformer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Api\Response\ResponseFactoryInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class OrderActionController extends AbstractApiController
{
    /**
     * Diese Route gibt ausführliche Informationen über eine bestimmte Bestellung zurück.
     * Per Default verhält diese Route sich wie die entsprechende Route der Shopware-API.
     * Dadurch lässt sich flexibel mit ihr arbeiten.
     * Es gibt zusätzlich die Möglichkeit den Header 'mothership-transform': true zu setzen.
     * Dann liefert die Route die Bestellinformationen in einem simpleren Format zurück, dass sich besser für eine
     * maschinelle Weiterverarbeitung eignet.
     *
     * @Since("6.0.0.0")
     * @Route(
     *     "/api/mothership/search/order/{orderId}",
     *     name="api.mothership.search.order.detail",
     *     methods={"GET"}
     * )
     *
     * // {{endpoint}}/api/mothership/order/2cd2c09ff2874a9b91fb4ca3b089ae59
     *
     * Die Annotations werden in dem Fall über die Entity und nicht über einen Controller definiert. Das ist
     * an der Stelle etwas schwieriger zu finden.
     *
     * @throws JsonException
     * @link www/vendor/shopware/core/System/CustomEntity/Api/CustomEntityApiController.php
     */
    public function searchOptionallyTransformedDetails(
        string $orderId,
        Request $request,
        Context $context,
        ResponseFactoryInterface $responseFactory,
        string $entityName = 'order',
        string $path = ''
    ): Response {
        if ($orderId && !Uuid::isValid($orderId)) {
            throw new InvalidUuidException($orderId);
        }
        $repository = $this->definitionRegistry->getRepository('order');
        $criteria = $this->getCriteria($orderId);

        $result = $context->scope(
            Context::CRUD_API_SCOPE,
            function (Context $context) use ($repository, $criteria): EntitySearchResult {
                return $repository->search($criteria, $context);
            }
        );

        $definition = $this->getDefinitionOfPath($entityName, $path, $context);

        $response = $responseFactory->createListingResponse($criteria, $result, $definition, $request, $context);

        if ($request->headers->get('mothership-transform')) {
            $transformer = new Transformer();
            $responseAsArray = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $transformer->init($responseAsArray);
            $transformedPayload = $transformer->map();
            $payloadAsString = json_encode($transformedPayload, JSON_THROW_ON_ERROR);
            $response->setContent($payloadAsString);
        }

        return $response;
//        return $this->json($result);
    }

    /**
     * @Route(
     *     "/api/mothership/search/order",
     *     name="api.mothership.search.order.short",
     *     requirements={"path"="(\/[0-9a-f]{32}\/(extensions\/)?[a-zA-Z-]+)*\/?$"},
     *     methods={"POST"}
     * )
     */
    public function searchShortInfo(
        Request $request,
        Context $context,
        string $entityName = 'order',
        string $path = ''
    ): Response {
        /**
         * @var Criteria                  $criteria
         * @var EntityRepository $repository
         */
        [$criteria, $repository] = $this->resolveSearch($request, $context, $entityName, $path);

        $criteria->addFilter()
            ->addAssociation('deliveries')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('documents.documentType')
            ->addSorting(new FieldSorting('createdAt'))
            ->addAssociation('transactions.state_machine_state');

        $result = $context->scope(
            Context::CRUD_API_SCOPE,
            function (Context $context) use ($repository, $criteria): EntitySearchResult {
                return $repository->search($criteria, $context);
            }
        );

        $data = [];
        /**
         * @var EntitySearchResult $result
         * @var OrderEntity        $order
         */
        foreach ($result->getElements() as $order) {
            /**
             * Wir benutzen hier den Null-safe operator (vgl. optional chaining in JavaScript) damit keine NullPointException auftreten kann.
             * Dadurch soll unsere Route robust werden und auch Ergebnisse zurückliefern, falls mal ein Wert bei einer Bestellung fehlt.
             * {@link https://php.watch/versions/8.0/null-safe-operator}
             */
            $firstOrderTransaction = $order->getTransactions()?->first();
            $firstOrderDelivery = $order->getDeliveries()?->first();
            $data[] = [
                'states' => [
                    'order_transaction_id'    => $firstOrderTransaction?->getId(),
                    'order_transaction_state' => $firstOrderTransaction?->getStateMachineState()?->getTechnicalName(),

                    'order_delivery_id'    => $firstOrderDelivery?->getId(),
                    'order_delivery_state' => $firstOrderDelivery?->getStateMachineState()?->getTechnicalName(),

                    'order_state' => $order->getStateMachineState()?->getTechnicalName(),
                    'order_id'    => $order->getId(),
                ],

                'invoice'                   => $this->getFirstInvoiceData($order),
                'order_number'              => $order->getOrderNumber(),
                'payment_method_id'         => $firstOrderTransaction?->getPaymentMethodId(),
                'payment_method_short_name' => $firstOrderTransaction?->getPaymentMethod()?->getShortName(),
                'created_at'                => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at'                => $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'total' => $result->getTotal(),
            'data'  => $data,
        ]);
    }

    /**
     * Gibt die aktuellste Rechnungsnummer und einen Link zum Download der Datei über zurück.
     * Zum Download der Rechnungsdatei wird dann folgende Shopware-API-Route verwendet: GET
     * {{endpoint}}/api/_action/document/<invoice_link>
     *
     * @param OrderEntity $orderEntity
     *
     * @return array
     */
    private function getFirstInvoiceData(OrderEntity $orderEntity): array
    {
        $invoiceNumber = null;
        $lastUpdated = null;
        $invoiceLink = null;
        foreach ($orderEntity->getDocuments() as $document) {
            if ($document->getFileType() === 'pdf' && array_key_exists('custom', $document->getConfig())
                && array_key_exists('invoiceNumber', $document->getConfig()['custom'])
                && (null === $lastUpdated || $document->getCreatedAt() > $lastUpdated)) {
                $invoiceNumber = $document->getConfig()['custom']['invoiceNumber'];
                $lastUpdated = $document->getCreatedAt();
                $invoiceLink = $document->getUniqueIdentifier() . '/' . $document->getDeepLinkCode();
            }
        }

        return [
            'invoice_number' => $invoiceNumber,
            'invoice_link'   => $invoiceLink,
        ];
    }

    /**
     * @param string $orderId
     *
     * @return Criteria
     */
    protected function getCriteria(string $orderId): Criteria
    {
        /** Criteria sind von hier übernommen, da hier auch das CheckoutOrderPlaced-Event gefeuert wird, was ursächlich für die
         * initiale Bestellbestätigung ist:
         *
         * @see \Shopware\Core\Checkout\Cart\SalesChannel\CartOrderRoute::order
         **/
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('id', $orderId))
            ->addAssociation('lineItems.product')
            ->addAssociation('currency')
            ->addAssociation('orderCustomer.customer')
            ->addAssociation('orderCustomer.salutation')
            ->addAssociation('language')
            ->addAssociation('deliveries.shippingMethod')
            ->addAssociation('deliveries.shippingOrderAddress.country')
            ->addAssociation('salesChannel')
            ->addAssociation('addresses.country')
            ->addAssociation('addresses.countryState')
            ->addAssociation('transactions')
            ->addAssociation('transactions.paymentMethod')
            ->addAssociation('documents.documentType')
            ->addSorting(new FieldSorting('createdAt'));

        return $criteria;
    }
}
