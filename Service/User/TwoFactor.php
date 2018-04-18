<?php

namespace Garlic\User\Service\User;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Garlic\User\Event\TwoFactorAuthenticationEvent;
use Garlic\User\Event\TwoFactorAuthenticationEvents;

/**
 * Class TwoFactor
 */
class TwoFactor
{
    /**
     * @var JWTManagerInterface
     */
    private $jwtManager;

    /**
     * @var GoogleAuthenticator
     */
    private $googleAauthenticator;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $sessionName;

    /**
     * @var Request
     */
    private $request;

    /**
     * Resetting constructor.
     *
     * @param JWTManagerInterface      $jwtManager
     * @param GoogleAuthenticator      $googleAauthenticator
     * @param UserManagerInterface     $userManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param string                   $sessionName
     */
    public function __construct(
        JWTManagerInterface $jwtManager,
        GoogleAuthenticator $googleAauthenticator,
        UserManagerInterface $userManager,
        EventDispatcherInterface $eventDispatcher,
        string $sessionName
    ) {
        $this->jwtManager = $jwtManager;
        $this->googleAauthenticator = $googleAauthenticator;
        $this->userManager = $userManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->sessionName = $sessionName;
    }

    /**
     * Set Request
     *
     * @param Request $request
     *
     * @return TwoFactor
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Two factor login activate
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function twoFactorActivate(UserInterface $user)
    {
        $user->setGoogleAuthenticatorSecret(
            $this->googleAauthenticator
                ->generateSecret()
        );

        $this->userManager->updateUser($user);

        $response = new JsonResponse(
            [
                'message' => 'two_factor_login_activate_success',
            ],
            JsonResponse::HTTP_ACCEPTED
        );

        $this->eventDispatcher
            ->dispatch(
                TwoFactorAuthenticationEvents::ACTIVATE_SUCCESS,
                new TwoFactorAuthenticationEvent($user, $this->request, $response)
            );

        return $response;
    }

    /**
     * Two factor login deactivate
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function twoFactorDeactivate(UserInterface $user)
    {
        if (!$user->getGoogleAuthenticatorSecret()) {
            return new JsonResponse(
                [
                    'message' => 'two_factor_login_already_deactivated',
                ],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $user
            ->setGoogleAuthenticatorSecret(null)
            ->setIsTwoFactorEnable(false);
        $this->userManager->updateUser($user);

        return new JsonResponse(
            [
                'message' => 'two_factor_login_deactivate_success',
            ],
            JsonResponse::HTTP_ACCEPTED
        );
    }

    /**
     * Get two factor code
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function twoFactorCode(UserInterface $user)
    {
        if (!$user->isTwoFactorLogin()) {
            return new JsonResponse(
                [
                    'message' => 'two_factor_login_not_activate',
                ],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(
            [
                'data' => [
                    'qr_code'     => $this->googleAauthenticator->getUrl($user),
                    'qr_content'  => $this->googleAauthenticator->getQRContent($user),
                    'secret_code' => $user->getGoogleAuthenticatorSecret(),
                ],
            ],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Two factor login
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function twoFactorLogin(UserInterface $user)
    {
        if (!$user->isTwoFactorEnable()) {
            $user->setIsTwoFactorEnable(true);
            $this->userManager->updateUser($user);
        }

        $response = new JsonResponse(
            [
                'token' => $this->jwtManager->create($user),
            ],
            JsonResponse::HTTP_OK
        );

        $trustedCookie = $this->request->getSession()
            ->get($this->sessionName);

        if ($trustedCookie instanceof Cookie) {
            $response->headers->setCookie($trustedCookie);
            $this->request->getSession()->set($this->sessionName, '');
        }

        return $response;
    }
}
