<?php

namespace RL\Security;

use Psr\Container\ContainerInterface;
use RL\Exception\NoApiTokenException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthTenantResolver
{
    protected int $tenant;

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param TokenStorageInterface $tokenStorage
     * @throws NoApiTokenException
     */
    public function resolveMeta(
        TokenStorageInterface $tokenStorage
    ): void {
        $token = $tokenStorage->getToken();

        if (!$token) {
            throw new NoApiTokenException('No token provided');
        }

        $tokenUser = $token->getUser();

        if ($tokenUser) {
            $this->tenant = (int) $tokenUser->getTenant();
        }
    }

    /**
     * @return int|null
     */
    public function getTenant(): ?int
    {
        return $this->tenant;
    }
}
