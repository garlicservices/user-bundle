<?php

namespace Garlic\User\Controller;

use Garlic\User\Traits\HelperTrait;
use Garlic\User\Traits\ControllerTrait;
use Garlic\User\Exception\AccountNotLinkedException;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Swagger\Annotations as SWG;

/**
 * Class SocialConnectController
 */
class SocialConnectController extends BaseController
{
    use ControllerTrait;
    use HelperTrait;

    /**
     * Action that handles the login 'form'. If connecting is enabled the
     * user will be redirected to the appropriate login urls or registration forms.
     *
     * @Route("/social/login", name="hwi_oauth_login")
     *
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function connect(Request $request)
    {
        $hasUser = $this->getUser(false) ? $this->isGranted('IS_AUTHENTICATED_REMEMBERED') : false;
        $error = $this->getErrorForRequest($request);

        $userInformation = null;
        if ($error instanceof AccountNotLinkedException) {
            $userInformation = $this
                ->getResourceOwnerByName($error->getResourceOwnerName())
                ->getUserInformation($error->getRawToken());
        }

        return $this->get('garlic_user.social_network.connect.service')
            ->setRequest($request)
            ->connect(
                $error,
                $hasUser,
                $userInformation,
                $this->getTargetPath($request->getSession())
            );
    }

    /**
     * Registration if there is no user logged in and connecting
     *
     * @Route("/social/connect/registration/{key}", name="hwi_oauth_connect_registration")
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
     * @param Request $request a request
     * @param string  $key     key used for retrieving the right information for the registration form
     *
     * @return Response
     *
     * @throws NotFoundHttpException if `connect` functionality was not enabled
     * @throws AccessDeniedException if any user is authenticated
     * @throws \Exception
     */
    public function registration(Request $request, $key)
    {
        $connect = $this->container->getParameter('hwi_oauth.connect');
        if (!$connect) {
            throw new NotFoundHttpException();
        }

        $hasUser = $this->isGranted('IS_AUTHENTICATED_REMEMBERED');
        if ($hasUser) {
            throw new AccessDeniedException('Cannot connect already registered account.');
        }

        $error = unserialize($this->get('snc_redis.default')->get($key));

        if (!$error instanceof AccountNotLinkedException || empty($error)) {
            throw new \Exception(
                'Cannot register an account.',
                0,
                $error instanceof \Exception ? $error : null
            );
        }

        $userInformation = $this
            ->getResourceOwnerByName($error->getResourceOwnerName())
            ->getUserInformation($error->getRawToken());

        return $this->get('garlic_user.social_network.connect.service')
            ->setRequest($request)
            ->registration($userInformation);
    }

    /**
     * Connects a user to a given account if the user is logged in and connect is enabled.
     *
     * @Route("/social/connect/service/{service}", name="hwi_oauth_connect_service")
     *
     * @param Request $request the active request
     * @param string  $service name of the resource owner to connect to
     *
     * @throws \Exception
     *
     * @return Response
     *
     * @throws NotFoundHttpException if `connect` functionality was not enabled
     * @throws AccessDeniedException if no user is authenticated
     */
    public function connectService(Request $request, $service)
    {
        $connect = $this->container->getParameter('hwi_oauth.connect');
        if (!$connect) {
            throw new NotFoundHttpException();
        }

        $hasUser = $this->isGranted('IS_AUTHENTICATED_REMEMBERED');
        if (!$hasUser) {
            throw new AccessDeniedException('Cannot connect an account.');
        }

        // Get the data from the resource owner
        $resourceOwner = $this->getResourceOwnerByName($service);

        $session = $request->getSession();
        $key = $request->query->get('key', time());
        $authUrl = $this->container->get('hwi_oauth.security.oauth_utils')
            ->getServiceAuthUrl($request, $resourceOwner);

        if ($resourceOwner->handles($request)) {
            $accessToken = $resourceOwner->getAccessToken(
                $request,
                $this->toHttps($authUrl)
            );

            // save in session
            $session->set('_hwi_oauth.connect_confirmation.'.$key, $accessToken);
        } else {
            $accessToken = $session->get('_hwi_oauth.connect_confirmation.'.$key);
        }

        // Redirect to the login path if the token is empty (Eg. User cancelled auth)
        if (null === $accessToken) {
            return $this->redirectToRoute($this->container->getParameter('hwi_oauth.failed_auth_path'));
        }

        $userInformation = $resourceOwner->getUserInformation($accessToken);

        // Show confirmation page?
        if (!$this->container->getParameter('hwi_oauth.connect.confirmation')) {
            return $this->getValidResponse($request, $service, $accessToken, $userInformation);
        }

        /** @var $form FormInterface */
        $form = $this->createForm('form')
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->getValidResponse($request, $service, $accessToken, $userInformation);
        }

        return $this->redirect($this->getTargetPath($session));
    }

    /**
     * Connects a user to a given account if the user is logged in and connect is enabled.
     *
     * @Route("/social/connect/{service}", name="oauth_service_redirect")
     *
     * @param Request $request
     * @param string  $service
     *
     * @return RedirectResponse
     */
    public function redirectToService(Request $request, $service)
    {
        try {
            $token = $request->query->get('bearer');
            if ($token) {
                $payload = $this->getPayloadOfToken($token);
                $request->getSession()->set('userId', $payload['id']);
            }

            $authorizationUrl = $this->toHttps(
                $this->container->get('hwi_oauth.security.oauth_utils')
                    ->getAuthorizationUrl(
                        $request,
                        $service
                    )
            );

        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        // Check for a return path and store it before redirect
        if ($request->hasSession()) {
            // initialize the session for preventing SessionUnavailableException
            $session = $request->getSession();
            $session->start();
            foreach ($this->container->getParameter('hwi_oauth.firewall_names') as $providerKey) {
                $sessionKey = '_security.'.$providerKey.'.target_path';

                $param = $this->container->getParameter('hwi_oauth.target_path_parameter');
                if (!empty($param) && $targetUrl = $request->get($param)) {
                    $session->set($sessionKey, $targetUrl);
                }

                if ($this->container->getParameter('hwi_oauth.use_referer') && !$session->has($sessionKey)
                    && ($targetUrl = $request->headers->get('Referer')) && $targetUrl !== $authorizationUrl) {
                    $session->set($sessionKey, $targetUrl);
                }
            }
        }

        return $this->redirect($authorizationUrl);
    }

    /**
     * Get list of social authorisation links
     *
     * @Route("/social/links", name="hwi_oauth_login_links")
     *
     * @Method({"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return social authorisation links."
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Server error"
     * )
     *
     * @return JsonResponse
     */
    public function getSocialLinks()
    {
        $resourceOwners = array_keys($this->container->getParameter('hwi_oauth.resource_owners'));

        return new JsonResponse(
            [
                'data' => array_combine(
                    $resourceOwners,
                    array_map(
                        function ($resourceOwnerName) {
                            return $this->generateUrl(
                                'hwi_oauth_service_redirect',
                                [
                                    'service' => $resourceOwnerName,
                                ]
                            );
                        },
                        $resourceOwners
                    )
                ),
            ],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Get valid response
     *
     * @param Request               $request
     * @param string                $service
     * @param string                $accessToken
     * @param UserResponseInterface $userInformation
     *
     * @return RedirectResponse
     */
    private function getValidResponse(Request $request, $service, $accessToken, UserResponseInterface $userInformation)
    {
        /** @var $currentToken OAuthToken */
        $currentToken = $this->get('security.token_storage')->getToken();
        $currentUser = $currentToken->getUser();

        $this->container->get('hwi_oauth.account.connector')->connect($currentUser, $userInformation);

        if ($currentToken instanceof OAuthToken) {
            // Update user token with new details
            $newToken =
                is_array($accessToken) &&
                (isset($accessToken['access_token']) || isset($accessToken['oauth_token'])) ?
                    $accessToken : $currentToken->getRawToken();

            $this->authenticateUser($request, $currentUser, $service, $newToken, false);
        }

        $token = $this->get('lexik_jwt_authentication.jwt_manager')
            ->create($this->getUser());

        return $this->redirect($this->getTargetPath($request->getSession()).'?'.http_build_query(['token' => $token]));
    }
}
