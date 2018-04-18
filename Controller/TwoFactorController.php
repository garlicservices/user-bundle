<?php

namespace Garlic\User\Controller;

use Garlic\User\Entity\User;
use Garlic\User\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * Class TwoFactorController
 */
class TwoFactorController extends Controller
{
    use ControllerTrait;

    /**
     * Activate two factor login.
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @Route("/two-factor-activate", name="two_factor_activate")
     *
     * @Method({"POST"})
     *
     * @SWG\Response(
     *     response=202,
     *     description="Two factor login activate success"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="To factor login already active"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function twoFactorActivate(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getGoogleAuthenticatorSecret()) {
            return new JsonResponse(
                [
                    'message' => 'two_factor_login_already_active',
                ],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return $this->get('garlic_user.user.two_factor.service')
            ->setRequest($request)
            ->twoFactorActivate($user);
    }

    /**
     * Get token if two factor authentication.
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @Route("/two-factor-login", name="two_factor_login")
     *
     * @Method({"POST"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return user token"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function twoFactorLogin(Request $request)
    {
        return $this->get('garlic_user.user.two_factor.service')
            ->setRequest($request)
            ->twoFactorLogin(
                $this->getUser()
            );
    }

    /**
     * Deactivate two factor login.
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @Route("/two-factor-deactivate", name="two_factor_deactivate")
     *
     * @Method({"POST"})
     *
     * @SWG\Response(
     *     response=202,
     *     description="Two factor login deactivate success"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Two factor login already deactivated"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @return JsonResponse
     */
    public function twoFactorDeactivate()
    {
        return $this->get('garlic_user.user.two_factor.service')
            ->twoFactorDeactivate(
                $this->getUser()
            );
    }

    /**
     * Get two factor login code.
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @Route("/two-factor-code", name="two_factor_code")
     *
     * @Method({"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return two factor login code"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Two factor login not activate"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @return JsonResponse
     */
    public function twoFactorCode()
    {
        return $this->get('garlic_user.user.two_factor.service')
            ->twoFactorCode(
                $this->getUser()
            );
    }
}
