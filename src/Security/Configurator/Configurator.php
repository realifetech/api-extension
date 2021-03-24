<?php

namespace RL\Security\Configurator;

use LS\AdminBundle\Entity\AdminUser;
use LS\ApiBundle\Repository\AccessTokenRepository;
use LS\ApiBundle\Security\AuthAppResolver;
use LS\ApiBundle\Security\Filter\AppFilter;
use LS\Apollo\App\Exception\AuthorizationException;
use LS\Apollo\App\Exception\NoApiTokenException;
use LS\Apollo\UserManagement\Repository\DeviceRepository;
use LS\UserManagementBundle\Entity\App;
use LS\UserManagementBundle\Repository\AppRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class Configurator
{
    /** @var ObjectManager */
    protected ObjectManager $em;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var Reader
     */
    protected $reader;

    /** @var Session */
    protected $session;
    private $appResolver;
    private $tokenRepository;
    private $appRepository;
    private $deviceRepository;

    /**
     * Configurator constructor.
     * @param ObjectManager         $em
     * @param TokenStorageInterface $tokenStorage
     * @param Reader                $reader
     * @param Session               $session
     * @param AuthAppResolver       $appResolver
     * @param AccessTokenRepository $tokenRepository
     * @param AppRepository         $appRepository
     * @param DeviceRepository      $deviceRepository
     */
    public function __construct(
        ObjectManager $em,
        TokenStorageInterface $tokenStorage,
        Reader $reader,
        Session $session,
        AuthAppResolver $appResolver,
        AccessTokenRepository $tokenRepository,
        AppRepository $appRepository,
        DeviceRepository $deviceRepository
    ) {
        $this->em               = $em;
        $this->tokenStorage     = $tokenStorage;
        $this->reader           = $reader;
        $this->session          = $session;
        $this->appResolver      = $appResolver;
        $this->tokenRepository  = $tokenRepository;
        $this->appRepository    = $appRepository;
        $this->deviceRepository = $deviceRepository;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var AppFilter $filter */
        $filter = $this->em->getFilters()->enable('app_filter');

        try {
            $path = $event->getRequest()->getPathInfo();

            try {
                $this->appResolver->resolveMeta(
                    $this->tokenStorage,
                    $event->getRequest(),
                    $this->tokenRepository,
                    $this->appRepository,
                    $this->deviceRepository
                );
            } catch (AuthorizationException $e) {
                $uri = $event->getRequest()->getRequestUri();

                if (!$this->isWebRequest($uri)) {
                    throw $e;
                }
            }

            $meta = $this->appResolver->getMeta();
            $app  = $meta['app'] ?? null;
            $user = $meta['user'] ?? null;

            if ($user || $app) {
                if (strpos(trim($path, '/'), 'v3') !== 0) {
                    if ($this->pathIs($path, 'admin') && !$this->pathIs($path, 'admin/login')) {
                        if (!$user instanceof AdminUser) {
                            throw new AccessDeniedException('Incorrect admin authentication');
                        }

                        $currentApp = $this->session->get('current_app', null);

                        $appIds = $this->getAppIds($user->getApps()
                            ->toArray());
                        if (!empty($appIds)) {
                            $filter->setParameter('apps', implode(',', str_replace("'", "", $appIds)));
                        }

                        if ($currentApp) {
                            $filter->setParameter('currentApp', $currentApp->getId());
                        } else {
                            $filter->setParameter('currentApp', AppFilter::DEFAULT_APP_ID);
                        }
                    } elseif (strpos(trim($path, '/'), 'v4') === 0) {
                        if (!$app instanceof App) {
                            throw new AccessDeniedException('Incorrect app authentication');
                        }

                        $currentApp = $app;

                        $filter->setParameter('currentApp', $currentApp->getId());
                    }

                    $filter->setAnnotationReader($this->reader);
                }
            }
        } catch (NoApiTokenException $e) {
            $filter->setParameter('currentApp', 0);//give access to no app
        }
    }

    private function isWebRequest($uri)
    {
        return strpos($uri, 'v3/web') !== false;
    }

    /**
     * @param string $path
     * @param string $subpath
     * @return bool
     */
    private function pathIs(string $path, string $subpath): bool
    {
        return strpos(trim($path, '/'), $subpath) === 0;
    }
}
