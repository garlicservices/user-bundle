<?php

namespace Garlic\User\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;

/**
 * Class JwtController
 */
class JwtController extends Controller
{
    /**
     * Get public key for jwt authentication
     *
     * @Route("/jwt/get-public-key/{token}", name="jwt_get_public_key")
     *
     * @Method({"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return public key for jwt authentication."
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Bad application token."
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    public function getPublicKey($token)
    {
        if (getenv('APPLICATION_TOKEN') !== $token) {
            throw new NotFoundHttpException('Resource not found.');
        }

        return new JsonResponse(
            [
                'data' => file_get_contents(
                    $this->container->getParameter(
                        'lexik_jwt_authentication.public_key_path'
                    )
                ),
            ],
            JsonResponse::HTTP_OK
        );
    }
}
