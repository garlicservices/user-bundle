<?php

namespace Garlic\User\Security;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Garlic\User\Traits\HelperTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

/**
 * Class OAuthListener
 */
class OAuthListener extends AbstractAuthenticationListener implements ContainerAwareInterface
{
    use HelperTrait;
    use ContainerAwareTrait;

    /**
     * @var ResourceOwnerMap
     */
    private $resourceOwnerMap;

    /**
     * @var array
     */
    private $checkPaths;

    /**
     * @param ResourceOwnerMap $resourceOwnerMap
     */
    public function setResourceOwnerMap(ResourceOwnerMap $resourceOwnerMap)
    {
        $this->resourceOwnerMap = $resourceOwnerMap;
    }

    /**
     * @param array $checkPaths
     */
    public function setCheckPaths(array $checkPaths)
    {
        $this->checkPaths = $checkPaths;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresAuthentication(Request $request)
    {
        // Check if the route matches one of the check paths
        foreach ($this->checkPaths as $checkPath) {
            if ($this->httpUtils->checkRequestPath($request, $checkPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $this->handleOAuthError($request);

        /* @var ResourceOwnerInterface $resourceOwner */
        list($resourceOwner, $checkPath) = $this->resourceOwnerMap->getResourceOwnerByRequest($request);

        if (!$resourceOwner) {
            throw new AuthenticationException('No resource owner match the request.');
        }

        if (!$resourceOwner->handles($request)) {
            throw new AuthenticationException('No oauth code in the request.');
        }

        // If resource owner supports only one url authentication, call redirect
        if ($request->query->has('authenticated') && $resourceOwner->getOption('auth_with_one_url')) {
            $request->attributes->set('service', $resourceOwner->getName());

            return new RedirectResponse(
                sprintf(
                    '%s?code=%s&authenticated=true',
                    $this->httpUtils->generateUri($request, 'hwi_oauth_connect_service'),
                    $request->query->get('code')
                )
            );
        }

        $resourceOwner->isCsrfTokenValid($request->get('state'));

        $accessToken = $resourceOwner->getAccessToken(
            $request,
            $this->toHttps($this->httpUtils->createRequest($request, $checkPath)->getUri())
        );

        $token = new OAuthToken($accessToken);
        $token->setResourceOwnerName($resourceOwner->getName());

        return $this->authenticationManager->authenticate($token);
    }

    /**
     * Detects errors returned by resource owners and transform them into
     * human readable messages.
     *
     * @param Request $request
     *
     * @throws AuthenticationException
     */
    private function handleOAuthError(Request $request)
    {
        $error = null;
        $query = $request->query;

        // Try to parse content if error was not in request query
        if ($query->has('error') || $query->has('error_code')) {
            if ($query->has('error_description') || $query->has('error_message')) {
                throw new AuthenticationException(
                    rawurldecode($query->get('error_description', $query->get('error_message')))
                );
            }

            $error = $this->getErrorOfContent($request);
        } elseif ($query->has('oauth_problem')) {
            $error = $query->get('oauth_problem');
        }

        if (null !== $error) {
            throw new AuthenticationException($error);
        }
    }

    /**
     * Get error of content
     *
     * @param Request $request
     *
     * @return array|null
     */
    private function getErrorOfContent(Request $request)
    {
        $error = null;
        $query = $request->query;
        $content = json_decode($request->getContent(), true);
        if (JSON_ERROR_NONE === json_last_error() && isset($content['error'])) {
            if (isset($content['error']['message'])) {
                throw new AuthenticationException($content['error']['message']);
            }

            if (isset($content['error']['code'])) {
                $error = $content['error']['code'];
            } elseif (isset($content['error']['error-code'])) {
                $error = $content['error']['error-code'];
            } else {
                $error = $query->get('error');
            }
        }

        return $error;
    }
}
