<?php

namespace Garlic\User\Service\User;

use Garlic\User\Traits\FormHelperTrait;
use Garlic\User\Traits\UserHelperTrait;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;

/**
 * Class Registration
 */
class Registration
{
    use FormHelperTrait;
    use UserHelperTrait;

    /**
     * @var FactoryInterface
     */
    private $formFactory;

    /**
     * @var JWTManagerInterface
     */
    private $jwtManager;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var int
     */
    private $resentEmailTtl;

    /**
     * @var Request
     */
    private $request;

    /**
     * Registration constructor.
     *
     * @param FactoryInterface         $formFactory
     * @param JWTManagerInterface      $jwtManager
     * @param UserManagerInterface     $userManager
     * @param MailerInterface          $mailer
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface      $translator
     * @param RouterInterface          $router
     * @param int                      $resentEmailTtl
     */
    public function __construct(
        FactoryInterface $formFactory,
        JWTManagerInterface $jwtManager,
        UserManagerInterface $userManager,
        MailerInterface $mailer,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        RouterInterface $router,
        int $resentEmailTtl = 3600
    ) {
        $this->formFactory = $formFactory;
        $this->jwtManager = $jwtManager;
        $this->userManager = $userManager;
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->router = $router;
        $this->resentEmailTtl = $resentEmailTtl;
    }

    /**
     * Set Request
     *
     * @param Request $request
     *
     * @return Registration
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Register user
     *
     * @return JsonResponse|Response
     */
    public function register()
    {
        /** @var UserInterface $user */
        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $this->request);
        $this->eventDispatcher->dispatch(
            FOSUserEvents::REGISTRATION_INITIALIZE,
            $event
        );

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->formFactory->createForm();
        $form->setData($user);
        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event = new FormEvent($form, $this->request);
                $this->eventDispatcher->dispatch(
                    FOSUserEvents::REGISTRATION_SUCCESS,
                    $event
                );

                $this->userManager->updateUser($user);

                $response = $event->getResponse();

                $this->eventDispatcher->dispatch(
                    FOSUserEvents::REGISTRATION_COMPLETED,
                    new FilterUserResponseEvent($user, $this->request, $response)
                );

                $this->userManager->updateUser($user);

                return new JsonResponse(
                    [
                        'key'     => 'user_create_success',
                        'message' => 'Account created',
                        'user'    => $this->getUserData($user),
                    ],
                    JsonResponse::HTTP_CREATED
                );
            }

            $event = new FormEvent($form, $this->request);
            $this->eventDispatcher
                ->dispatch(
                    FOSUserEvents::REGISTRATION_FAILURE,
                    $event
                );

            if (null !== $response = $event->getResponse()) {
                return new JsonResponse(
                    [
                        'message' => $response->getContent(),
                    ],
                    $response->getStatusCode()
                );
            }
        }

        return new JsonResponse(
            [
                'errors' => $this->getErrorMessages($form),
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
    }

    /**
     * Resent confirm email
     *
     * @param UserInterface $user
     *
     * @return JsonResponse
     */
    public function resentConfirm(UserInterface $user)
    {
        $confirmationUrl = $this->request->request->get('confirm_url');
        if (null !== $user && !$user->timeToResentEmailConfirmation($this->resentEmailTtl)) {
            $user->setConfirmationUrl($confirmationUrl);

            $this->mailer->sendConfirmationEmailMessage($user);
            $user->setConfirmEmailResentAt(new \DateTime());
            $this->userManager->updateUser($user);

            return new JsonResponse(
                [
                    'message' => 'Mail successfully sent',
                    'key'     => 'email_confirmation_resent',
                ]
            );
        }

        return new JsonResponse(
            [
                'errors' => [
                    'key'     => 'resent_mail_already_sent',
                    'timeout' => ceil($user->timeToResentEmailConfirmation($this->resentEmailTtl) / 60),
                    'message' => 'Next resent email confirmation will be available in '
                        .ceil($user->timeToResentEmailConfirmation($this->resentEmailTtl) / 60).' minutes',
                ],
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function confirm(UserInterface $user)
    {
        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $this->request);
        $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_CONFIRM, $event);
        $this->userManager->updateUser($user);

        if (null === $response = $event->getResponse()) {
            $response = new JsonResponse(
                [
                    'message' => 'user_confirmed_success',
                    'token'   => $this->jwtManager->create($user),
                ]
            );
        }

        $this->eventDispatcher->dispatch(
            FOSUserEvents::REGISTRATION_CONFIRMED,
            new FilterUserResponseEvent($user, $this->request, $response)
        );

        return $response;
    }
}
