<?php

namespace Garlic\User\Controller;

use Garlic\User\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Swagger\Annotations as SWG;

/**
 * Class UserController
 */
class UserController extends Controller
{
    use ControllerTrait;

    /**
     * Get user data.
     *
     * @Route("/user", name="get_user")
     *
     * @Method({"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return user data"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Access denied"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @return JsonResponse
     */
    public function user()
    {
        return new JsonResponse(
            [
                'data' => $this->getUserData(
                    $this->getUser()
                ),
            ],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Edit the user.
     *
     * @Route("/edit", name="edit")
     *
     * @Method({"POST"})
     *
     * @SWG\Parameter(
     *     name="brandName",
     *     in="formData",
     *     type="string",
     *     description="Brand name"
     * )
     * @SWG\Parameter(
     *     name="website",
     *     in="formData",
     *     type="string",
     *     description="Website"
     * )
     * @SWG\Parameter(
     *     name="firstName",
     *     in="formData",
     *     type="string",
     *     description="First name"
     * )
     * @SWG\Parameter(
     *     name="lastName",
     *     in="formData",
     *     type="string",
     *     description="Last name"
     * )
     * @SWG\Parameter(
     *     name="phone",
     *     in="formData",
     *     type="string",
     *     description="Phone"
     * )
     * @SWG\Parameter(
     *     name="country",
     *     in="formData",
     *     type="string",
     *     description="Country"
     * )
     * @SWG\Response(
     *     response=202,
     *     description="User update success."
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Validation error"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Access denied"
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
    public function edit(Request $request)
    {
        return $this->get('garlic_user.user.edit.service')
            ->setRequest($request)
            ->edit(
                $this->getUser()
            );
    }

    /**
     * Change user password.
     *
     * @Route("/change-password", name="change_password")
     *
     * @Method({"POST"})
     *
     * @SWG\Parameter(
     *     name="fos_user_change_password_form[current_password]",
     *     in="formData",
     *     type="string",
     *     description="Current Password"
     * )
     * @SWG\Parameter(
     *     name="fos_user_change_password_form[plainPassword][first]",
     *     in="formData",
     *     type="string",
     *     description="New Password"
     * )
     * @SWG\Parameter(
     *     name="fos_user_change_password_form[plainPassword][second]",
     *     in="formData",
     *     type="string",
     *     description="Confirm new Password"
     * )
     * @SWG\Response(
     *     response=202,
     *     description="User password update success."
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Validation error"
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Access denied"
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
    public function changePassword(Request $request)
    {
        return $this->get('garlic_user.user.edit.service')
            ->setRequest($request)
            ->changePassword(
                $this->getUser()
            );
    }
}
