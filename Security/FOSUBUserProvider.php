<?php

namespace Garlic\User\Security;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseProvider;
use Garlic\User\Exception\AccountNotLinkedException;

/**
 * Class FOSUBUserProvider
 */
class FOSUBUserProvider extends BaseProvider
{
    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        if (null === $user = $this->userManager
                ->findUserByEmail($response->getEmail())) {
            throw new AccountNotLinkedException(sprintf("User '%s' not found.", $response->getEmail()));
        }

        $setterPropertyName = 'set'.ucfirst($this->getProperty($response));
        $setterTokenName = 'set'.ucfirst($response->getResourceOwner()->getName()).'AccessToken';
        $user->$setterPropertyName($response->getUsername());
        $user->$setterTokenName($response->getAccessToken());
        $this->userManager->updateUser($user);

        return $user;
    }
}
