<?php

namespace RL\Provider;

use RL\Entity\ApiKey;
use RL\Repository\ApiKeyRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class TenantProvider implements UserProviderInterface
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
        $user = $this->apiKeyRepository->findByToken($username);

        if (!$user) {
            throw new AuthenticationCredentialsNotFoundException();
        }
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
        if (!$user instanceof ApiKey) {
            throw new UnsupportedUserException(sprintf(
                'Expected an instance of RL\Entity\ApiKey, but got "%s".',
                get_class($user)
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function supportsClass($class)
    {
        $userClass = ApiKey::class;

        return $userClass === $class || is_subclass_of($class, $userClass);
    }
}
