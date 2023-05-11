<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Api;

use MothershipSimpleApi\Service\SimpleProductCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JsonException;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ProductActionController extends AbstractController
{
    protected SimpleProductCreator $simpleProductCreator;

    public function __construct(\MothershipSimpleApi\Service\SimpleProductCreator $simpleProductCreator)
    {
        $this->simpleProductCreator = $simpleProductCreator;
    }

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
     *     "/api/mothership/product",
     *     name="api.mothership.product.create",
     *     methods={"POST"}
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
    public function exampleApi(Request $request, Context $context): JsonResponse
    {
        $responseAsArray = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->simpleProductCreator->createEntity($responseAsArray, $context);
        return new JsonResponse(['You successfully created your first controller route']);
    }
}
