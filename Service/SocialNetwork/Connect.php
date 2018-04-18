<?php

namespace Garlic\User\Service\SocialNetwork;

use Garlic\User\Event\AccountNotLinkedExceptionEvent;
use Garlic\User\Event\SocialNetworkConnectEvents;
use Garlic\User\Traits\RedirectTrait;
use Garlic\User\Traits\UserHelperTrait;
use Garlic\User\Traits\FormHelperTrait;
use Garlic\User\Exception\AccountNotLinkedException;
use Predis\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use HWI\Bundle\OAuthBundle\Connect\AccountConnectorInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Form\RegistrationFormHandlerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Form\Factory\FactoryInterface;

/**
 * Class Connect
 */
class Connect
{
    use RedirectTrait;
    use FormHelperTrait;
    use UserHelperTrait;

    /**
     * @var FactoryInterface
     */
    private $formFactory;

    /**
     * @var RegistrationFormHandlerInterface
     */
    private $formHandler;

    /**
     * @var AccountConnectorInterface
     */
    private $accountConnector;

    /**
     * @var JWTTokenManagerInterface
     */
    private $jwtManager;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ClientInterface
     */
    protected $redis;

    /**
     * @var bool
     */
    protected $connect;

    /**
     * @var Request
     */
    private $request;

    /**
     * Registration constructor.
     *
     * @param FactoryInterface                 $formFactory
     * @param RegistrationFormHandlerInterface $formHandler
     * @param AccountConnectorInterface        $accountConnector
     * @param JWTTokenManagerInterface         $jwtManager
     * @param UserManagerInterface             $userManager
     * @param EventDispatcherInterface         $eventDispatcher
     * @param TranslatorInterface              $translator
     * @param RouterInterface                  $router
     * @param bool                             $connect
     * @param ClientInterface                  $redis
     */
    public function __construct(
        FactoryInterface $formFactory,
        RegistrationFormHandlerInterface $formHandler,
        AccountConnectorInterface $accountConnector,
        JWTTokenManagerInterface $jwtManager,
        UserManagerInterface $userManager,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        RouterInterface $router,
        bool $connect,
        ClientInterface $redis
    ) {
        $this->formFactory = $formFactory;
        $this->formHandler = $formHandler;
        $this->accountConnector = $accountConnector;
        $this->jwtManager = $jwtManager;
        $this->userManager = $userManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->router = $router;
        $this->connect = $connect;
        $this->redis = $redis;
    }

    /**
     * Set Request
     *
     * @param Request $request
     *
     * @return Connect
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Connect user
     *
     * @param AccountNotLinkedException  $error
     * @param bool                       $hasUser
     * @param UserResponseInterface|null $userInformation
     * @param string|null                $redirectUrl
     *
     * @return RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function connect(
        AccountNotLinkedException $error,
        $hasUser,
        UserResponseInterface $userInformation = null,
        $redirectUrl = null
    ) {
        $queryString = null;
        $removeSession = false;

        $event = new AccountNotLinkedExceptionEvent($error, $redirectUrl, $this->request);
        $this->eventDispatcher->dispatch(
            SocialNetworkConnectEvents::ACCOUNT_NOT_LINKED_EXCEPTION_CONNECT_PROCESS,
            $event
        );

        $response = $event->getResponse();
        if ($response instanceof Response) {
            return $response;
        }

        if ($this->connect
            && !$hasUser
            && $error instanceof AccountNotLinkedException
        ) {
            $key = time().rand(100, 1000);
            $this->redis->setEx(
                $key,
                getenv('SOCIAL_ERROR_TTL'),
                serialize($error)
            );

            $queryString = [
                'registerUrl' => substr(
                    $this->generateUrl(
                        'hwi_oauth_connect_registration',
                        [
                            'key' => $key,
                        ]
                    ),
                    1
                ),
                'email'       => $userInformation->getEmail(),
            ];
        }

        return $this->createRedirectResponse(
            $redirectUrl,
            $queryString,
            $removeSession
        );
    }

    /**
     * Register user
     *
     * @param UserResponseInterface $userInformation
     *
     * @return JsonResponse
     */
    public function registration(UserResponseInterface $userInformation)
    {
        $form = $this->formFactory->createForm();
        if ($this->formHandler->process($this->request, $form, $userInformation)) {
            /** @var UserInterface $user */
            $user = $form->getData();
            $this->setUserData($userInformation, $user);
            $this->accountConnector->connect($user, $userInformation);

            return new JsonResponse(
                [
                    'key'     => 'user_create_success',
                    'message' => 'Account created',
                    'user'    => $this->getUserData($user),
                    'token'   => $this->jwtManager->create($user),
                ],
                JsonResponse::HTTP_CREATED
            );
        }

        return new JsonResponse(
            [
                'errors' => $this->getErrorMessages($form),
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
    }

    /**
     * Set user data
     *
     * @param UserResponseInterface $userInformation
     * @param UserInterface         $user
     */
    private function setUserData(UserResponseInterface $userInformation, UserInterface $user)
    {
        if (!empty($profilePicture = $userInformation->getProfilePicture())) {
            $user->setProfilePicture($profilePicture);
        }

        if (!empty($firstName = $userInformation->getFirstName())) {
            $user->setFirstName($firstName);
        }

        if (!empty($lastName = $userInformation->getLastName())) {
            $user->setLastName($lastName);
        }

        if (!empty($website = $userInformation->getWebsite())) {
            $user->setWebsite($website);
        }

        if (!empty($gender = $userInformation->getGender())) {
            $user->setGender($gender);
        }

        $user->setEnabled(true);
    }
}