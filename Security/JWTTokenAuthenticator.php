<?php

namespace Garlic\User\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator as BaseJWTTokenAuthenticator;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class JWTTokenAuthenticator
 */
class JWTTokenAuthenticator extends BaseJWTTokenAuthenticator
{
    /**
     * @var JWTTokenManagerInterface
     */
    private $jwtManager;

    /**
     * @var TokenExtractorInterface
     */
    private $tokenExtractor;

    /**
     * @var string
     */
    private $twoFactorCheckPath;

    /**
     * @param JWTTokenManagerInterface $jwtManager
     * @param EventDispatcherInterface $dispatcher
     * @param TokenExtractorInterface  $tokenExtractor
     * @param string                   $twoFactorCheckPath
     */
    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $dispatcher,
        TokenExtractorInterface $tokenExtractor,
        string $twoFactorCheckPath
    ) {
        $this->jwtManager = $jwtManager;
        $this->tokenExtractor = $tokenExtractor;
        $this->twoFactorCheckPath = $twoFactorCheckPath;

        parent::__construct($jwtManager, $dispatcher, $tokenExtractor);
    }

    /**
     * Returns a decoded JWT token extracted from a request.
     *
     * {@inheritdoc}
     *
     * @return PreAuthenticationJWTUserToken
     *
     * @throws InvalidTokenException If an error occur while decoding the token
     * @throws ExpiredTokenException If the request token is expired
     */
    public function getCredentials(Request $request)
    {
        $tokenExtractor = $this->getTokenExtractor();

        if (!$tokenExtractor instanceof TokenExtractorInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Method "%s::getTokenExtractor()" must return an instance of "%s".',
                    __CLASS__,
                    TokenExtractorInterface::class
                )
            );
        }

        if (false === ($jsonWebToken = $tokenExtractor->extract($request))) {
            return;
        }

        $preAuthToken = new PreAuthenticationJWTUserToken($jsonWebToken);

        try {
            if (!$payload = $this->jwtManager->decode($preAuthToken)) {
                throw new InvalidTokenException('Invalid JWT Token');
            }

            /* Two factor authentication */
            if ($request->get('_route') !== $this->twoFactorCheckPath
                && isset($payload['tfa']) && !$payload['tfa']) {
                throw new NotAcceptableHttpException('two_factor_authentication_error');
            }

            $preAuthToken->setPayload($payload);
        } catch (JWTDecodeFailureException $e) {
            if (JWTDecodeFailureException::EXPIRED_TOKEN === $e->getReason()) {
                throw new ExpiredTokenException('Expired JWT Token', 401);
            }

            throw new InvalidTokenException('Invalid JWT Token', 401, $e);
        }

        return $preAuthToken;
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $authException
     * @return \Symfony\Component\HttpFoundation\Response|void|null
     * @throws \Exception
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $authException)
    {
        throw new \Exception($authException->getMessage(), $authException->getCode());
    }
}
