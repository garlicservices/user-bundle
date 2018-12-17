<?php

namespace Garlic\User\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Garlic\User\Entity\User;

/**
 * Class JWTCreatedListener
 */
class JWTCreatedListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        /** @var User $user */
        $user = $event->getUser();
        $payload = $event->getData();
        $payload['ip'] = $request->getClientIp();

        if ($user instanceof User) {
            $payload['id'] = $user->getId();
            $payload['email'] = $user->getEmail();
            $payload['username'] = $user->getUsername();
        } else {
            $payload['email'] = $user->getEmail();
            $payload['username'] = $user->getUsername();
        }

        if ($user instanceof TwoFactorInterface
            && $user->getGoogleAuthenticatorSecret()
            && $user->isTwoFactorEnable()
        ) {
            $payload['tfa'] = $user->isAuthenticated();
        }

        $event->setData($payload);
    }
}
