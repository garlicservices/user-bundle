<?php

namespace Garlic\User\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Garlic\User\Traits\HelperTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Class AuthenticationSuccessHandler
 */
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use HelperTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var JWTManager
     */
    protected $jwtManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * AuthenticationSuccessHandler constructor.
     *
     * @param ContainerInterface       $container
     * @param JWTManager               $jwtManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        $container,
        JWTManager $jwtManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->container = $container;
        $this->jwtManager = $jwtManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        return $this->handleAuthenticationSuccess($request, $token->getUser());
    }

    /**
     * Handle Authentication Success
     *
     * @param Request       $request
     * @param UserInterface $user
     * @param string        $jwt
     *
     * @return JWTAuthenticationSuccessResponse
     */
    public function handleAuthenticationSuccess(Request $request, UserInterface $user, $jwt = null)
    {
        if (null === $jwt) {
            $jwt = $this->jwtManager->create($user);
        }

        $response = new JWTAuthenticationSuccessResponse($jwt);
        $event = new AuthenticationSuccessEvent(['token' => $jwt], $user, $response);

        $this->dispatcher->dispatch(Events::AUTHENTICATION_SUCCESS, $event);

        $targetPath = $this->getTargetPath($request->getSession());

        if ($targetPath) {
            $response->headers->set(
                'Location',
                $this->getTargetPath($request->getSession()).'?'.http_build_query(['token' => $jwt])
            );
        }

        $response->headers->clearCookie('PHPSESSID');

        return $response;
    }
}
