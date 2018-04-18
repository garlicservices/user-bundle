<?php

namespace Garlic\User\Service\User;

use Garlic\User\Traits\FormHelperTrait;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Edit
 */
class Edit
{
    use FormHelperTrait;

    /**
     * @var FactoryInterface
     */
    private $profileFormFactory;

    /**
     * @var FactoryInterface
     */
    private $changePasswordFormFactory;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Request
     */
    private $request;

    /**
     * Edit constructor.
     *
     * @param FactoryInterface         $profileFormFactory
     * @param FactoryInterface         $changePasswordFormFactory
     * @param UserManagerInterface     $userManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        FactoryInterface $profileFormFactory,
        FactoryInterface $changePasswordFormFactory,
        UserManagerInterface $userManager,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator
    ) {
        $this->profileFormFactory = $profileFormFactory;
        $this->changePasswordFormFactory = $changePasswordFormFactory;
        $this->userManager = $userManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
    }

    /**
     * Set Request
     *
     * @param Request $request
     *
     * @return Edit
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function edit(UserInterface $user)
    {
        $event = new GetResponseUserEvent($user, $this->request);
        $this->eventDispatcher->dispatch(
            FOSUserEvents::PROFILE_EDIT_INITIALIZE,
            $event
        );

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->profileFormFactory->createForm();
        $form->setData($user);
        $form->submit($this->request->request->get('data'));

        if ($form->isSubmitted() && $form->isValid()) {
            $event = new FormEvent($form, $this->request);
            $this->eventDispatcher->dispatch(
                FOSUserEvents::PROFILE_EDIT_SUCCESS,
                $event
            );

            $this->userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                return new JsonResponse(
                    [
                        'message' => 'user_update_success',
                    ],
                    JsonResponse::HTTP_ACCEPTED
                );
            }

            $this->eventDispatcher->dispatch(
                FOSUserEvents::PROFILE_EDIT_COMPLETED,
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

    /**
     * Change user password
     *
     * @param UserInterface $user
     *
     * @return JsonResponse|Response
     */
    public function changePassword(UserInterface $user)
    {
        $event = new GetResponseUserEvent($user, $this->request);
        $this->eventDispatcher->dispatch(
            FOSUserEvents::CHANGE_PASSWORD_INITIALIZE,
            $event
        );

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->changePasswordFormFactory->createForm();
        $form->setData($user);

        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = new FormEvent($form, $this->request);
            $this->eventDispatcher->dispatch(
                FOSUserEvents::CHANGE_PASSWORD_SUCCESS,
                $event
            );

            $this->userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                return new JsonResponse(
                    [
                        'message' => 'user_password_update_success',
                    ],
                    JsonResponse::HTTP_ACCEPTED
                );
            }

            $this->eventDispatcher->dispatch(
                FOSUserEvents::CHANGE_PASSWORD_COMPLETED,
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
