<?php

namespace Garlic\User\Security;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * Class LdapUser
 */
class LdapUser implements AdvancedUserInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var bool
     */
    private $accountNonExpired;

    /**
     * @var bool
     */
    private $credentialsNonExpired;

    /**
     * @var bool
     */
    private $accountNonLocked;

    /**
     * @var array
     */
    private $roles;

    /**
     * LdapUser constructor.
     *
     * @param string $username
     * @param string $password
     * @param array  $roles
     * @param bool   $enabled
     * @param bool   $userNonExpired
     * @param bool   $credentialsNonExpired
     * @param bool   $userNonLocked
     */
    public function __construct(
        $username,
        $password,
        array $roles = [],
        $enabled = true,
        $userNonExpired = true,
        $credentialsNonExpired = true,
        $userNonLocked = true
    ) {
        if ('' === $username || null === $username) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        $this->username = $username;
        $this->password = $password;
        $this->enabled = $enabled;
        $this->accountNonExpired = $userNonExpired;
        $this->credentialsNonExpired = $credentialsNonExpired;
        $this->accountNonLocked = $userNonLocked;
        $this->roles = $roles;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return $this->accountNonExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return $this->accountNonLocked;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return $this->credentialsNonExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
}
