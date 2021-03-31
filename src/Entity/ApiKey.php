<?php

namespace RL\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;
use RL\Traits\DateStorageTrait;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="RL\Repository\ApiKeyRepository")
 * @ORM\Table(name="api_key", indexes={
 *     @Index(columns={"expire_at"}),
 *     @Index(columns={"status"}),
 *     @Index(columns={"app_id", "token", "status"})
 * }, uniqueConstraints={
 *     @UniqueConstraint(columns={"token"})
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class ApiKey implements UserInterface
{
    use DateStorageTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="integer", name="app_id")
     */
    private int $app;

    /**
     * @ORM\Column(type="string")
     */
    private string $token;

    /**
     * @ORM\Column(type="string", name="status")
     */
    private string $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $expireAt;

    /**
     * @var ApiKeyAccess[]
     * @ORM\OneToMany(targetEntity="RL\Entity\ApiKeyAccess", mappedBy="apiKey", cascade={"all"},
     *     orphanRemoval=true, fetch="EAGER")
     * @ORM\JoinTable(name="api_key_access")
     */
    private $apiKeyAccesses;

    public function __construct()
    {
        $this->apiKeyAccesses = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getApp(): int
    {
        return $this->app;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return DateTime|null
     */
    public function getExpireAt(): ?DateTime
    {
        return $this->expireAt;
    }

    /**
     * @return ArrayCollection
     */
    public function getApiKeyAccesses()
    {
        return $this->apiKeyAccesses;
    }

    public function getUsername()
    {
        return $this->token;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function getPassword()
    {
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }
}
