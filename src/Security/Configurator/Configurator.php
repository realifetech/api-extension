<?php

namespace RL\Security\Configurator;

use RL\Security\Filter\AppFilter;
use RL\Exception\NoApiTokenException;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\Annotations\Reader;

class Configurator
{
    const CURRENT_APP = 27;

    /** @var ObjectManager */
    protected ObjectManager $em;

    /** @var Reader */
    protected Reader $reader;

    public function __construct(
        ObjectManager $em,
        Reader $reader
    ) {
        $this->em = $em;
        $this->reader = $reader;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        /** @var AppFilter $filter */
        $filter = $this->em->getFilters()->enable('app_filter');

        try {
            $path = $event->getRequest()->getPathInfo();

            if (strpos(trim($path, '/'), 'v4') === 0) {
                $filter->setParameter('currentApp', self::CURRENT_APP);
            }

            $filter->setAnnotationReader($this->reader);
        } catch (NoApiTokenException $e) {
            $filter->setParameter('currentApp', 0);
        }
    }
}
