<?php

namespace RL\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Rl\Traits\DateStorageTrait;

/**
 * @ORM\Entity(repositoryClass="RL\Repository\ApiKeyAccessRepository")
 * @ORM\Table(name="api_key_access", indexes={
 *     @Index(columns={"route"}),
 *     @Index(columns={"route", "method"})
 * }, uniqueConstraints={
 *     @UniqueConstraint(columns={"api_key_id", "route", "method"})
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class ApiKeyAccess
{
    use DateStorageTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="RL\Entity\ApiKey", inversedBy="apiKeyAccesses")
     * @ORM\JoinColumn(name="api_key_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private ApiKey $apiKey;

    /**
     * @ORM\Column(type="string", name="route")
     */
    private string $route;

    /**
     * @ORM\Column(type="string", name="method")
     */
    private string $method;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $updatedAt;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return ApiKey
     */
    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
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
}
