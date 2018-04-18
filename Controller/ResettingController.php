<?php

namespace Garlic\User\Controller;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Swagger\Annotations as SWG;

/**
 * Class ResettingController
 */
class ResettingController extends Controller
{
    /**
     * Request reset user password: submit form and send email.
     *
     * @Route("/resetting/send-email", name="resetting_send_email")
     *
     * @Method({"POST"})
     *
     * @SWG\Parameter(
     *     name="username",
     *     in="formData",
     *     type="string",
     *     description="Username (email)"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Reset mail successful requested"
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Reset mail already requested"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Email does not exist"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function sendEmail(Request $request)
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

        return $this->get('garlic_user.user.resetting.service')
            ->setRequest($request)
            ->sendResettingEmail($user);
    }

    /**
     * Change user password.
     *
     * @Route("/resetting/reset/{token}", name="resetting_reset")
     *
     * @Method({"POST"})
     *
     * @SWG\Parameter(
     *     name="fos_user_resetting_form[plainPassword][first]",
     *     in="formData",
     *     type="string",
     *     description="New password"
     * )
     * @SWG\Parameter(
     *     name="fos_user_resetting_form[plainPassword][second]",
     *     in="formData",
     *     type="string",
     *     description="Confirm new password"
     * )
     * @SWG\Response(
     *     response=202,
     *     description="User password reset success."
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Validation error"
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Confirmation token not found"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    public function reset(Request $request, $token)
    {
        /** @var $user UserInterface */
        $user = $this->get('fos_user.user_manager')
            ->findUserByConfirmationToken($token);

        if (null === $user) {
            return new JsonResponse(
                [
                    'errors' => [
                        'message' =>
                            sprintf('The user with "confirmation token" does not exist for value "%s"', $token),
                        'key'     => 'confirmation_token_not_found',
                    ],
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        if (!$user->isPasswordRequestNonExpired($this->getParameter('fos_user.resetting.retry_ttl'))) {
            return new JsonResponse(
                [
                    'errors' => [
                        'message' => 'Request confirmation link expired',
                        'key'     => 'password_request_token_expired',
                    ],
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->get('garlic_user.user.resetting.service')
            ->setRequest($request)
            ->reset($user);
    }
}
