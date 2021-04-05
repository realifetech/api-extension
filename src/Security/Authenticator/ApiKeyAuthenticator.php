<?php

namespace RL\Security\Authenticator;

use InvalidArgumentException;
use RL\Exception\AccessDeniedException;
use RL\Provider\TenantProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        try {
            $method = $token->getAttribute('method');
            $route  = $token->getAttribute('route');
        } catch (InvalidArgumentException $exception) {
            return false;
        }

        /**
         * @var $userProvider TenantProvider
         */
        if ($tokenRecord = $userProvider->validateTokenRouteAndMethod($token->getCredentials(), $route, $method)) {
            $token->setUser($tokenRecord);

            return $token;
        } else {
            throw new AccessDeniedException(403, 'Access Denied.');
        }
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function createToken(Request $request, $providerKey)
    {
        $apiKey = $request->headers->get('X-API-Key');

        if (!$apiKey) {
            throw new AccessDeniedException(403, 'Authentication Required.');
        }

        $preAuthenticatedToken = new PreAuthenticatedToken('anon.', $apiKey, $providerKey);

        $preAuthenticatedToken->setAttribute('method', $request->getMethod());
        $preAuthenticatedToken->setAttribute('route', $request->getPathInfo());

        return $preAuthenticatedToken;
    }
}
