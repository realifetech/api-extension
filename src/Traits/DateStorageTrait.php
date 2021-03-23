<?php

namespace RL\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait DateStorageTrait
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 */
trait DateStorageTrait
{
    /**
     * @ORM\PrePersist()
     */
    public function onPrePersist()
    {
        //using Doctrine DateTime here
        $this->createdAt = new \DateTime('now');
        //using Doctrine DateTime here
        $this->updatedAt = new \DateTime('now');
    }

    /**
     * @ORM\PreUpdate()
     */
    public function onPreUpdate()
    {
        //using Doctrine DateTime here
        $this->updatedAt = new \DateTime('now');
    }
}
