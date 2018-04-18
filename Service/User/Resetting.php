<?php

namespace Garlic\User\Service\User;

use Garlic\User\Traits\FormHelperTrait;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class Resetting
 */
class Resetting
{
    use FormHelperTrait;

    /**
     * @var FactoryInterface
     */
    private $formFactory;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

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
    private $retryTtl;

    /**
     * @var Request
     */
    private $request;

    /**
     * Resetting constructor.
     *
     * @param FactoryInterface         $formFactory
     * @param UserManagerInterface     $userManager
     * @param TokenGeneratorInterface  $tokenGenerator
     * @param MailerInterface          $mailer
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface      $translator
     * @param int                      $retryTtl
     */
    public function __construct(
        FactoryInterface $formFactory,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface $mailer,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        int $retryTtl
    ) {
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->retryTtl = $retryTtl;
    }

    /**
     * Set Request
     *
     * @param Request $request
     *
     * @return Resetting
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Send resetting email
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function sendResettingEmail(UserInterface $user)
    {
        if (null !== $user && !$user->isPasswordRequestNonExpired($this->retryTtl)) {
            $user->setConfirmationUrl($this->request->request->get('confirm_url'));
            $event = new GetResponseUserEvent($user, $this->request);
            $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_RESET_REQUEST, $event);
            if (null !== $event->getResponse()) {
                return $event->getResponse();
            }

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->tokenGenerator->generateToken());
            }

            /* Dispatch confirm event */
            $event = new GetResponseUserEvent($user, $this->request);
            $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_SEND_EMAIL_CONFIRM, $event);
            if (null !== $event->getResponse()) {
                return $event->getResponse();
            }

            $this->mailer->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $this->userManager->updateUser($user);

            return new JsonResponse(
                [
                    'message' => [
                        'message' => 'Reset mail successful requested',
                        'key'     => 'reset_mail_success_requested',
                    ],
                ]
            );
        }

        return new JsonResponse(
            [
                'errors' => [
                    'key'     => 'reset_mail_already_requested',
                    'message' => 'Reset mail already requested, you can`t do it more often then '
                        .ceil($this->retryTtl / 3600).' hours',
                ],
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
    }

    /**
     * Reset user password
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function reset(UserInterface $user)
    {
        $form = $this->formFactory->createForm();
        $form->setData($user);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = new FormEvent($form, $this->request);
            $this->eventDispatcher->dispatch(
                FOSUserEvents::RESETTING_RESET_SUCCESS,
                $event
            );

            $this->userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                return new JsonResponse(
                    [
                        'message' => 'user_password_reset_success',
                    ],
                    JsonResponse::HTTP_ACCEPTED
                );
            }

            $this->eventDispatcher->dispatch(
                FOSUserEvents::RESETTING_RESET_COMPLETED,
                new FilterUserResponseEvent($user, $this->request, $response)
            );

            return $response;
        }

        return new JsonResponse(
            [
                'errors' => $this->getErrorMessages($form),
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
    }
}
