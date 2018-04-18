<?php

namespace Garlic\User\Traits;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Trait ControllerTrait
 */
trait ControllerTrait
{
    use FormHelperTrait;
    use UserHelperTrait;
    use RedirectTrait;

    /**
     * Get user
     *
     * @param bool $check
     *
     * @return UserInterface
     */
    protected function getUser($check = true)
    {
        /** @var UserInterface $user */
        $user = parent::getUser();

        if ($check && (!is_object($user) || !$user instanceof UserInterface)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $user;
    }

    /**
     * Get payload of token
     *
     * @param string $token
     *
     * @return array
     */
    protected function getPayloadOfToken($token)
    {
        $decoder = $this->container
            ->get('lexik_jwt_authentication.encoder');

        return $decoder->decode($token);
    }
}
