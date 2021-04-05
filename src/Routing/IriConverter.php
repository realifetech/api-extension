<?php

namespace RL\Routing;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Routing\RouterInterface;

class IriConverter implements IriConverterInterface
{
    const V4_PREFIX = '/v4';

    /**
     * @var IriConverterInterface
     */
    private IriConverterInterface $iriConverter;
    /**
     * @var Reader
     */
    private Reader $reader;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    public function __construct(IriConverterInterface $iriConverter, Reader $reader, RouterInterface $router)
    {
        $this->iriConverter = $iriConverter;
        $this->reader = $reader;
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    public function getItemFromIri(string $iri, array $context = [])
    {
        $requestedResource = isset($context['request_uri']) ? $this->router->match($context['request_uri']) : [];

        $requestedResourceIsMicroserviceAware = isset($requestedResource['_api_resource_class']) ?
            $this->isMicroserviceAware($requestedResource['_api_resource_class']):
            true;

        if (((isset($context['resource_class']) && $this->isMicroserviceAware($context['resource_class']) ||
                    !$context['fetch_data']) && $this->isNotV4($iri)) && $requestedResourceIsMicroserviceAware
        ) {
            $iri = self::V4_PREFIX . $iri;
        }

        return $this->iriConverter->getItemFromIri($iri, $context);
    }

    /**
     * @inheritDoc
     */
    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        $iri = $this->iriConverter->getIriFromItem($item, $referenceType);
        if ($this->isMicroserviceAware(ClassUtils::getClass($item)) && $this->isV4($iri)) {
            return $this->removePrefix($iri);
        }

        return $iri;
    }

    /**
     * @inheritDoc
     */
    public function getIriFromResourceClass(
        string $resourceClass,
        int $referenceType = UrlGeneratorInterface::ABS_PATH
    ): string {
        $iri = $this->iriConverter->getIriFromResourceClass($resourceClass, $referenceType);
        if ($this->isMicroserviceAware($resourceClass) && $this->isV4($iri)) {
            return $this->removePrefix($iri);
        }

        return $iri;
    }

    /**
     * @inheritDoc
     */
    public function getItemIriFromResourceClass(
        string $resourceClass,
        array $identifiers,
        int $referenceType = UrlGeneratorInterface::ABS_PATH
    ): string {
        return $this->iriConverter->getItemIriFromResourceClass($resourceClass, $identifiers, $referenceType);
    }

    /**
     * @inheritDoc
     */
    public function getSubresourceIriFromResourceClass(
        string $resourceClass,
        array $identifiers,
        int $referenceType = UrlGeneratorInterface::ABS_PATH
    ): string {
        return $this->iriConverter->getSubresourceIriFromResourceClass($resourceClass, $identifiers, $referenceType);
    }

    /**
     * @param string $resourceClass
     * @return boolean
     */
    private function isMicroserviceAware(string $resourceClass): bool
    {
        try {
            $class = new \ReflectionClass($resourceClass);
            if ($this->reader->getClassAnnotation(
                $class,
                'RL\Annotation\MicroserviceAware'
            )) {
                return true;
            }
        } catch (\ReflectionException $e) {
        }

        return false;
    }

    /**
     * @param string $iri
     * @return string
     */
    private function removePrefix(string $iri): string
    {
        return str_replace(self::V4_PREFIX, '', $iri);
    }

    /**
     * @param string $iri
     * @return string
     */
    public function addPrefix(string $iri): string
    {
        if (!$this->isV4($iri)) {
            $iri = self::V4_PREFIX . $iri;
        }

        return $iri;
    }

    /**
     * @param string $iri
     * @return bool
     */
    private function isNotV4(string $iri): bool
    {
        return preg_match('#'.self::V4_PREFIX.'#', $iri) !== 1;
    }

    /**
     * @param string $iri
     * @return bool
     */
    private function isV4(string $iri): bool
    {
        return preg_match('#'.self::V4_PREFIX.'#', $iri) === 1;
    }
}
