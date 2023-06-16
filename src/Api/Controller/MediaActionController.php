<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Api\Controller;

use JsonException;
use MothershipSimpleApi\Service\Domain\Media\MediaCreator;
use MothershipSimpleApi\Service\Exception\InvalidCurrencyCodeException;
use MothershipSimpleApi\Service\Exception\InvalidSalesChannelNameException;
use MothershipSimpleApi\Service\Exception\InvalidTaxValueException;
use MothershipSimpleApi\Service\SimpleProductCreator;
use MothershipSimpleApi\Service\SimpleProductSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class MediaActionController extends AbstractController
{
    public function __construct(protected MediaCreator $mediaCreator)
    {
    }

    /**
     * Erstellt ein Produkt anhand der übergebenen Daten.
     * Um ein Produkt zu aktualisieren, müssen alle relevanten Produktdaten übergeben werden,
     * auch diese die gleich bleiben sollen.
     *
     * @Since("6.0.0.0")
     * @Route(
     *     "/api/_sync/mothership/media",
     *     name="api.mothership.media.create",
     *     methods={"POST"}
     * )
     *
     * Die Annotations werden in dem Fall über die Entity und nicht über einen Controller definiert. Das ist
     * an der Stelle etwas schwieriger zu finden.
     *
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
     * @throws JsonException
     * @link www/vendor/shopware/core/System/CustomEntity/Api/CustomEntityApiController.php
     */
    public function create(Request $request, Context $context): JsonResponse
    {
        $responseAsArray = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->mediaCreator->createEntity($responseAsArray, $context);
        return new JsonResponse(['Product successfully created or updated']);
    }
}
