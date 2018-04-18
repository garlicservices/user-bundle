<?php

namespace Garlic\User\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait TwoFactorTrait
 */
trait TwoFactorTrait
{
    /**
     * Is two factor authenticated
     *
     * @var bool
     */
    protected $authenticated = false;

    /**
     * @ORM\Column(name="googleAuthenticatorSecret", type="string", nullable=true)
     */
    protected $googleAuthenticatorSecret;

    /**
     * @ORM\Column(type="smallint", name="is_two_factor_enable", length=2, nullable=true)
     */
    protected $isTwoFactorEnable;

    /**
     * Get google authenticator secret
     *
     * @return string
     */
    public function getGoogleAuthenticatorSecret()
    {
        return $this->googleAuthenticatorSecret;
    }

    /**
     * Set google authenticator secret
     *
     * @param string $googleAuthenticatorSecret
     *
     * @return $this
     */
    public function setGoogleAuthenticatorSecret($googleAuthenticatorSecret)
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;

        return $this;
    }

    /**
     * Is Two Factor Enable
     *
     * @return boolean
     */
    public function isTwoFactorEnable()
    {
        return $this->isTwoFactorEnable;
    }

    /**
     * Set is Two Factor Enable
     *
     * @param boolean $isTwoFactorEnable
     *
     * @return $this
     */
    public function setIsTwoFactorEnable($isTwoFactorEnable)
    {
        $this->isTwoFactorEnable = $isTwoFactorEnable;

        return $this;
    }

    /**
     * Is authenticated
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * Set authenticated
     *
     * @param boolean $authenticated
     *
     * @return $this
     */
    public function setAuthenticated($authenticated)
    {
        $this->authenticated = $authenticated;

        return $this;
    }

    /**
     * Is two factor login user
     *
     * @return bool
     */
    public function isTwoFactorLogin()
    {
        return !empty($this->getGoogleAuthenticatorSecret());
    }
}
