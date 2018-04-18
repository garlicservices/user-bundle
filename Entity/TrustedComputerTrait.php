<?php

namespace Garlic\User\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait TrustedComputerTrait
 */
trait TrustedComputerTrait
{
    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $trusted;

    /**
     * Add trusted computer
     *
     * @param string    $token
     * @param \DateTime $validUntil
     */
    public function addTrustedComputer($token, \DateTime $validUntil)
    {
        $this->trusted[$token] = $validUntil->format("r");
    }

    /**
     * Is trusted computer
     *
     * @param string $token
     *
     * @return bool
     */
    public function isTrustedComputer($token)
    {
        if (isset($this->trusted[$token])) {
            $now = new \DateTime();
            $validUntil = new \DateTime($this->trusted[$token]);

            return $now < $validUntil;
        }

        return false;
    }
}
