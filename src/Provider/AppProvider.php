<?php

namespace RL\Provider;

use RL\Repository\ApiKeyRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AppProvider implements UserProviderInterface
{
    /** @var ApiKeyRepository */
    private ApiKeyRepository $apiKeyRepository;

    public function __construct(
        ApiKeyRepository $apiKeyRepository
    ) {
        $this->apiKeyRepository = $apiKeyRepository;
    }

    /**
     * @inheritdoc
     */
    public function loadUserByUsername($username)
    {
        //pass
    }

    public function validateTokenRouteAndMethod($token, $route, $method)
    {
        if ($token = $this->apiKeyRepository->findByToken($token)) {
            $accesses = $token->getApiKeyAccesses();

            foreach ($accesses as $access) {
                if (fnmatch(
                        $access->getRoute(),
                        $route
                    ) && (strtoupper($access->getMethod()) === 'ANY' || strcasecmp(
                            $method,
                            $access->getMethod()
                        ) == 0)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function refreshUser(UserInterface $user)
    {
    }

    /**
     * @inheritdoc
     */
    public function supportsClass($class)
    {
    }
}
