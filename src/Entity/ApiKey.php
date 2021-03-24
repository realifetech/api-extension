<?php

namespace RL\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Rl\Traits\DateStorageTrait;

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
class ApiKey
{
    use DateStorageTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="integer", name="app")
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
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime|null $createdAt
     */
    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     */
    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return DateTime|null
     */
    public function getExpireAt(): ?DateTime
    {
        return $this->expireAt;
    }

    /**
     * @param DateTime|null $expireAt
     */
    public function setExpireAt(?DateTime $expireAt): void
    {
        $this->expireAt = $expireAt;
    }

    /**
     * @return ArrayCollection
     */
    public function getApiKeyAccesses()
    {
        return $this->apiKeyAccesses;
    }

    /**
     * @param array $apiKeyAccess
     */
    public function setApiKeyAccesses(array $apiKeyAccess)
    {
        $this->apiKeyAccesses = $apiKeyAccess;
    }

    /**
     * @param ApiKeyAccess $apiKeyAccess
     */
    public function addApiKeyAccess(ApiKeyAccess $apiKeyAccess)
    {
        if (!$this->apiKeyAccesses->contains($apiKeyAccess)) {
            $apiKeyAccess->setApiKey($this);
            $this->apiKeyAccesses[] = $apiKeyAccess;
        }
    }

    /**
     * @param ApiKeyAccess $apiKeyAccess
     * @return bool
     */
    public function removeApiKeyAccess(ApiKeyAccess $apiKeyAccess): bool
    {
        return $this->apiKeyAccesses->removeElement($apiKeyAccess);
    }
}
