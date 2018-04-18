<?php

namespace Garlic\User\Traits;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Trait RedirectTrait
 */
trait RedirectTrait
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Create redirect response
     *
     * @param string $url
     * @param mixed  $queryString
     * @param bool   $removeSession
     *
     * @return RedirectResponse
     */
    protected function createRedirectResponse($url, $queryString = null, $removeSession = false)
    {
        if (is_array($queryString)) {
            $url = $url.'?'.http_build_query($queryString);
        } elseif (null !== $queryString) {
            $url = $url.'?'.$queryString;
        }

        $response = new RedirectResponse($url);
        if ($removeSession) {
            $response->headers->clearCookie('PHPSESSID');
        }

        return $response;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $referenceType
     *
     * @return string
     */
    protected function generateUrl(
        string $route,
        array $parameters = [],
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ) : string {
        if (!$this->router instanceof RouterInterface) {
            $this->router = $this->container->get('router');
        }

        return $this->router->generate($route, $parameters, $referenceType);
    }
}
