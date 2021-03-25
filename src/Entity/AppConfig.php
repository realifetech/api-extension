<?php

namespace RL\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="RL\Repository\AppConfigRepository")
 * @ORM\Table(name="app_config", indexes={
 *     @Index(columns={"key"})
 * }, uniqueConstraints={
 *     @UniqueConstraint(columns={"app_id", "key"})
 * })
 */
class AppConfig
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $app;

    /**
     * @ORM\Column(name="`key`", type="string")
     */
    protected string $key;

    /**
     * @ORM\Column(name="value", type="text")
     */
    protected string $value;

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
     * @param int $app
     */
    public function setApp(int $app): void
    {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
