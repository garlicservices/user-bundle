<?php

namespace Garlic\User\Security;

use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\Validation\CodeValidatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Class TwoFactorProvider
 */
class TwoFactorProvider implements TwoFactorProviderInterface
{
    /**
     * @var CodeValidatorInterface
     */
    private $authenticator;

    /**
     * @var string
     */
    private $authCodeParameter;

    /**
     * TwoFactorProvider constructor.
     *
     * @param CodeValidatorInterface $authenticator
     * @param string                 $authCodeParameter
     */
    public function __construct(CodeValidatorInterface $authenticator, $authCodeParameter)
    {
        $this->authenticator = $authenticator;
        $this->authCodeParameter = $authCodeParameter;
    }

    /**
     * Begin Google authentication process.
     *
     * @param AuthenticationContextInterface $context
     *
     * @return bool
     */
    public function beginAuthentication(AuthenticationContextInterface $context)
    {
        // Check if user can do email authentication
        $user = $context->getUser();

        return $user instanceof TwoFactorInterface && $user->getGoogleAuthenticatorSecret();
    }

    /**
     * Ask for Google authentication code.
     *
     * @param AuthenticationContextInterface $context
     *
     * @return Response|null
     */
    public function requestAuthenticationCode(AuthenticationContextInterface $context)
    {
        $user = $context->getUser();
        $request = $context->getRequest();

        $authCode = $request->get($this->authCodeParameter);

        if (null !== $authCode) {
            if ($this->authenticator->checkCode($user, $authCode)) {
                $context->setAuthenticated(true);
                $user->setAuthenticated(true);
            } else {
                throw new NotAcceptableHttpException('two_factor_authentication_error');
            }
        }
    }
}
