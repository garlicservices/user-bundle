<?php

namespace Garlic\User\Traits;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Trait HelperTrait
 */
trait HelperTrait
{
    /**
     * Get target path
     *
     * @param SessionInterface $session
     *
     * @return string|null
     */
    private function getTargetPath(SessionInterface $session)
    {
        foreach ($this->container->getParameter('hwi_oauth.firewall_names') as $providerKey) {
            $sessionKey = '_security.'.$providerKey.'.target_path';
            if ($session->has($sessionKey)) {
                return $session->get($sessionKey);
            }
        }

        return null;
    }

    /**
     * Transform http to https
     *
     * @param string $url
     * @param string $protocol
     *
     * @return string
     */
    private function toHttps($url, $protocol = null)
    {
        if (null === $protocol) {
            $protocol = getenv('PROTOCOL');
        }

        if ('https' === $protocol) {
            $url = str_replace(
                [
                    'http:',
                    'http%3A',
                ],
                [
                    'https:',
                    'https%3A',
                ],
                $url
            );
        }

        return $url;
    }
}
