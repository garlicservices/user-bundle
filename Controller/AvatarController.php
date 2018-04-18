<?php

namespace Garlic\User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Swagger\Annotations as SWG;

/**
 * Class AvatarController
 */
class AvatarController extends Controller
{
    /**
     * Change user avatar.
     *
     * @Route("/change-avatar", name="change_avatar")
     *
     * @Method({"POST"})
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     type="file",
     *     description="Image"
     * )
     * @SWG\Response(
     *     response=202,
     *     description="Change user avatar success."
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
     * @return JsonResponse
     */
    public function changeAvatar(Request $request)
    {
        return $this->get('garlic_user.user.avatar.service')
            ->setRequest($request)
            ->changeAvatar(
                $this->getUser()
            );
    }

    /**
     * Remove user avatar.
     *
     * @Route("/remove-avatar", name="remove_avatar")
     *
     * @Method({"POST"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Remove user avatar success."
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
    public function removeAvatar()
    {
        return $this->get('garlic_user.user.avatar.service')
            ->removeAvatar($this->getUser());
    }
}
