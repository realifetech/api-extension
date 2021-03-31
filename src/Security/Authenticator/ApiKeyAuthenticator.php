<?php

namespace RL\Security\Authenticator;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RL\Provider\AppProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    private EntityManagerInterface $em;

    private AppProvider $userProvider;

    public function __construct(EntityManagerInterface $em, AppProvider $userProvider) {
        $this->em = $em;
        $this->userProvider = $userProvider;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return true;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        return [
            'token'  => $request->headers->get('X-API-Key'),
            'method' => $request->getRealMethod(),
            'route'  => $request->getPathInfo()
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $apiKey = $credentials['token'];

        if (null === $apiKey) {
            return false;
        }

        return $userProvider->loadUserByUsername($apiKey);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        try {
            $method = $credentials['method'];
            $route  = $credentials['route'];
        } catch (InvalidArgumentException $exception) {
            return false;
        }

        return $this->userProvider->validateTokenRouteAndMethod($user->getToken(), $route, $method);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse([
            'message' => 'Authentication Required'
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse([
            'message' => 'Authentication Required'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
