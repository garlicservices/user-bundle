<?php

namespace Garlic\User\Traits;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Trait UserHelperTrait
 */
trait UserHelperTrait
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Return transformed user data
     *
     * @param UserInterface $user
     *
     * @return array
     */
    protected function getUserData(UserInterface $user)
    {
        if (!$this->router instanceof RouterInterface) {
            $this->router = $this->container->get('router');
        }

        $userData = $user->getSerializeFields();
        if (!empty($userData['profilePicture'])
            && false === strpos($userData['profilePicture'], 'https://')
            && false === strpos($userData['profilePicture'], 'http://')) {
            $userData['profilePicture'] = getenv('PROTOCOL').'://'.
                $this->router->getContext()->getHost().'/'.getenv(
                    'AVATAR_RELATIVE_DIRECTORY'
                ).'/'.$this->generatePathByName($userData['profilePicture']).'/'.$userData['profilePicture'];
        }

        return $userData;
    }

    /**
     * Generate path to file by name
     *
     * @param string $string
     *
     * @return null|string|string[]
     */
    protected function generatePathByName($string)
    {
        return preg_replace('/(.{2})(.{2})(.{2})(.{2})(.*)/i', '$1/$2/$3/$4', $string);
    }
}
