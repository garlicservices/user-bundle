<?php

namespace Garlic\User\Entity;

/**
 * Trait SocialNetworkTrait
 *
 * @SWG\Definition()
 */
trait SocialNetworkTrait
{
    /**
     * Facebook id
     *
     * @var string
     *
     * @ORM\Column(type="string", name="facebook_id", nullable=true, length=64)
     *
     * @SWG\Property()
     */
    private $facebookId;

    /**
     * Google id
     *
     * @var string
     *
     * @ORM\Column(type="string", name="google_id", nullable=true, length=64)
     *
     * @SWG\Property()
     */
    private $googleId;

    /**
     * Facebook access token
     *
     * @var string
     */
    private $facebookAccessToken;

    /**
     * Google access token
     *
     * @var string
     */
    private $googleAccessToken;

    /**
     * Get facebook id
     *
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * Set facebook id
     *
     * @param string $facebookId
     *
     * @return $this
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    /**
     * Get google id
     *
     * @return string
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * Set google id
     *
     * @param string $googleId
     *
     * @return $this
     */
    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;

        return $this;
    }

    /**
     * Set facebook access token
     *
     * @param string $facebookAccessToken
     *
     * @return $this
     */
    public function setFacebookAccessToken($facebookAccessToken)
    {
        $this->facebookAccessToken = $facebookAccessToken;

        return $this;
    }

    /**
     * Get facebook access token
     *
     * @return string
     */
    public function getFacebookAccessToken()
    {
        return $this->facebookAccessToken;
    }

    /**
     * Set google access token
     *
     * @param string $googleAccessToken
     *
     * @return $this
     */
    public function setGoogleAccessToken($googleAccessToken)
    {
        $this->googleAccessToken = $googleAccessToken;

        return $this;
    }

    /**
     * Get google access token
     *
     * @return string
     */
    public function getGoogleAccessToken()
    {
        return $this->googleAccessToken;
    }
}
