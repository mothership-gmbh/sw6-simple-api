<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Api;

use JsonException;
use MothershipSimpleApi\Service\SimpleCouponCreator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class CouponActionController extends AbstractController
{
    protected SimpleCouponCreator $simpleCouponCreator;

    public function __construct(SimpleCouponCreator $simpleCouponCreator)
    {
        $this->simpleCouponCreator = $simpleCouponCreator;
    }

    /**
     * @Since("6.0.0.0")
     * @Route(
     *     "/api/mothership/coupon",
     *     name="api.mothership.coupon.create",
     *     methods={"POST"}
     * )
     *
     * @param Request $request
     * @param Context $context
     *
     * @return JsonResponse
     * @throws JsonException
     */
    public function createCoupon(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $promoCode = $this->simpleCouponCreator->create($data, $context);

        return new JsonResponse(['code' => $promoCode->get('code')]);
    }
}
