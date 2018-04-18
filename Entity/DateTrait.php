<?php

namespace Garlic\User\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait DateTrait
 */
trait DateTrait
{
    /**
     * Create date
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="created")
     */
    private $created;

    /**
     * Update date
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", name="updated")
     */
    private $updated;

    /**
     * Get create date
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set create date
     *
     * @param \DateTime $created
     *
     * @return $this
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get update date
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set update date
     *
     * @param \DateTime $updated
     *
     * @return $this
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Set updatedAt and createdAt to current if value not specified time on prePersist event
     *
     * @ORM\PrePersist
     */
    public function preCreateChangeDate()
    {
        $this->created = $this->created ?: new \DateTime();
        $this->updated = $this->updated ?: new \DateTime();
    }

    /**
     * Set updatedAt to current time on preUpdate event
     *
     * @ORM\PreUpdate
     */
    public function preUpdateChangeDate()
    {
        $this->updated = new \DateTime();
    }
}
