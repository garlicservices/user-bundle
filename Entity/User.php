<?php

namespace Garlic\User\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class User
 */
abstract class User extends BaseUser
{
    /**
     * Used for paste confirmation url for confirm email
     *
     * @var string
     */
    private $confirmationUrl;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    protected $confirmEmailResentAt;

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get confirmation url
     *
     * @return string
     */
    public function getConfirmationUrl()
    {
        return $this->confirmationUrl;
    }

    /**
     * Set confirmation url
     *
     * @param string $confirmationUrl
     */
    public function setConfirmationUrl(string $confirmationUrl)
    {
        $this->confirmationUrl = $confirmationUrl;
    }

    /**
     * @return \DateTime
     */
    public function getConfirmEmailResentAt()
    {
        return $this->confirmEmailResentAt;
    }

    /**
     * @param \DateTime $confirmEmailResentAt
     */
    public function setConfirmEmailResentAt(\DateTime $confirmEmailResentAt)
    {
        $this->confirmEmailResentAt = $confirmEmailResentAt;
    }

    /**
     * Checks whether the confirmation request not abused and return when cant send email
     *
     * @param int $ttl Requests older than this many seconds will be considered abused
     *
     * @return int
     */
    public function timeToResentEmailConfirmation($ttl)
    {
        if (!$this->getConfirmEmailResentAt() instanceof \DateTime) {
            return false;
        }

        $timeToResent = $this->getConfirmEmailResentAt()->getTimestamp() + $ttl - time();
        if ($timeToResent <= 0) {
            return false;
        }

        return $timeToResent;
    }
}
