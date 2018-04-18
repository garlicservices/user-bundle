<?php

namespace Garlic\User\Security;

use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedCookieManager;
use Symfony\Component\HttpFoundation\Response;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationHandlerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

/**
 * Class TrustedFilter
 */
class TrustedFilter implements AuthenticationHandlerInterface
{
    /**
     * @var AuthenticationHandlerInterface
     */
    private $authHandler;

    /**
     * Manages trusted computer cookies.
     *
     * @var TrustedCookieManager
     */
    private $cookieManager;

    /**
     * If trusted computer feature is enabled.
     *
     * @var bool
     */
    private $useTrustedOption;

    /**
     * @var string
     */
    private $trustedName;

    /**
     * Construct the trusted computer layer.
     *
     * @param AuthenticationHandlerInterface $authHandler
     * @param TrustedCookieManager           $cookieManager
     * @param bool                           $useTrustedOption
     * @param string                         $trustedName
     */
    public function __construct(
        AuthenticationHandlerInterface $authHandler,
        TrustedCookieManager $cookieManager,
        $useTrustedOption,
        $trustedName
    ) {
        $this->authHandler = $authHandler;
        $this->cookieManager = $cookieManager;
        $this->useTrustedOption = $useTrustedOption;
        $this->trustedName = $trustedName;
    }

    /**
     * Check if user is on a trusted computer, otherwise call TwoFactorProviderRegistry.
     *
     * @param AuthenticationContextInterface $context
     */
    public function beginAuthentication(AuthenticationContextInterface $context)
    {
        $request = $context->getRequest();
        $user = $context->getUser();
        $context->setUseTrustedOption($this->useTrustedOption);

        // Skip two-factor authentication on trusted computers
        if ($context->useTrustedOption() && $this->cookieManager->isTrustedComputer($request, $user)) {
            $user->setAuthenticated(true);

            return;
        }

        $this->authHandler->beginAuthentication($context);
    }

    /**
     * Call TwoFactorProviderRegistry, set trusted computer cookie if requested.
     *
     * @param AuthenticationContextInterface $context
     *
     * @return Response|null
     */
    public function requestAuthenticationCode(AuthenticationContextInterface $context)
    {
        $request = $context->getRequest();
        $user = $context->getUser();

        $context->setUseTrustedOption($this->useTrustedOption); // Set trusted flag
        $this->authHandler->requestAuthenticationCode($context);

        // Set trusted cookie
        if ($context->isAuthenticated() && $context->useTrustedOption() && $request->get($this->trustedName)) {
            $cookie = $this->cookieManager->createTrustedCookie($request, $user);
            $request->getSession()->set($this->trustedName, $cookie);
        }

        return null;
    }
}
