<?php

namespace Garlic\User\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RequestSubscriber
 */
class RequestSubscriber implements EventSubscriberInterface
{
    use TargetPathTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * RequestSubscriber constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (null === $event->getRequest()->server->get('HTTP_REFERER')) {
            return;
        }

        foreach ($this->container->getParameter('hwi_oauth.firewall_names') as $providerKey) {
            $session = $event->getRequest()->getSession();
            $this->saveTargetPath(
                $session,
                $providerKey,
                $event->getRequest()->server->get('HTTP_REFERER')
            );
        }
    }
}
