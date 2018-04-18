<?php

namespace Garlic\User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;

/**
 * Class RegistrationController
 */
class RegistrationController extends Controller
{
    /**
     * User registration
     *
     * @Route("/register", name="register")
     *
     * @Method({"POST"})
     *
     * @SWG\Parameter(
     *     name="fos_user_registration_form[email]",
     *     in="formData",
     *     type="string",
     *     required=true,
     *     description="Email"
     * )
     * @SWG\Parameter(
     *     name="fos_user_registration_form[plainPassword][first]",
     *     in="formData",
     *     type="string",
     *     required=true,
     *     description="Password"
     * )
     * @SWG\Parameter(
     *     name="fos_user_registration_form[plainPassword][second]",
     *     in="formData",
     *     type="string",
     *     required=true,
     *     description="Confirm Password"
     * )
     * @SWG\Parameter(
     *     name="fos_user_registration_form[userType]",
     *     in="formData",
     *     type="integer",
     *     required=true,
     *     description="User type"
     * )
     * @SWG\Response(
     *     response=201,
     *     description="User registration success."
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Validation error"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function register(Request $request)
    {
        return $this->get('garlic_user.user.registration.service')
            ->setRequest($request)
            ->register();
    }

    /**
     * Resent the confirmation email
     *
     * @Route("/register/confirm-resent", name="register_confirmation_resent")
     *
     * @Method({"POST"})
     *
     * @SWG\Parameter(
     *     name="username",
     *     in="formData",
     *     type="string",
     *     required=true,
     *     description="Email"
     * )
     * @SWG\Parameter(
     *     name="confirm_url",
     *     in="formData",
     *     type="string",
     *     required=true,
     *     description="Confirm url"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="User mail sent success."
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Validation error"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function resentConfirm(Request $request)
    {
        $username = $request->request->get('username');

        /** @var $user UserInterface */
        $user = $this->get('fos_user.user_manager')
            ->findUserByUsernameOrEmail($username);

        if (null === $user) {
            return new JsonResponse(
                [
                    'errors' => [
                        'message' =>
                            sprintf('The user with "email" does not exist for value "%s"', $username),
                        'key'     => 'user_not_found',
                    ],
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        if ($user->isEnabled()) {
            return new JsonResponse(
                [
                    'errors' => [
                        'message' =>
                            sprintf('The user with "email" already confirmed for value "%s"', $username),
                        'key'     => 'user_already_confirmed',
                    ],
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->get('garlic_user.user.registration.service')
            ->setRequest($request)
            ->resentConfirm($user);
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @Route("/register/confirm/{token}", name="register_confirmation")
     *
     * @Method({"POST"})
     *
     * @SWG\Response(
     *     response=201,
     *     description="User registration success."
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Validation error"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param Request $request
     * @param string  $token
     *
     * @return JsonResponse|Response
     */
    public function confirm(Request $request, $token)
    {
        $user = $this->get('fos_user.user_manager')
            ->findUserByConfirmationToken($token);
        if (null === $user) {
            throw new NotFoundHttpException(
                sprintf('The user with confirmation token "%s" does not exist', $token)
            );
        }

        return $this->get('garlic_user.user.registration.service')
            ->setRequest($request)
            ->confirm($user);
    }
}
