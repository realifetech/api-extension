<?php

namespace RL\Routing;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Doctrine\Common\Annotations\Reader;
use RL\Exception\InvalidIRIResourceException;
use Symfony\Component\Routing\RouterInterface;

class IriConverter implements IriConverterInterface
{
    const V4_PREFIX = '/v4';

    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;

    /** @var Reader */
    private Reader $reader;

    /** @var RouterInterface */
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
    public function getItemFromIri(string $iri, array $context = []): object
    {
        if (((isset($context['resource_class']) || !$context['fetch_data']) && $this->isNotV4($iri))) {
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

        if ($this->isV4($iri)) {
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

        if ($this->isV4($iri)) {
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

    private function isV4(string $iri)
    {
        return preg_match('#'.self::V4_PREFIX.'#', $iri) === 1;
    }

    /**
     * @param string $resourcePrefix
     * @param int|string $id
     * @return string
     */
    public function convertResourceIDtoIRI(string $resourcePrefix, $id): string
    {
        return $resourcePrefix.(int)$id;
    }

    /**
     * @param string $resourcePrefix
     * @param ?string $iri
     * @return int
     * @throws InvalidIRIResourceException
     */
    public function convertResourceIRItoID(string $resourcePrefix, ?string $iri): int
    {
        if (is_null($iri)
            || preg_match('#^('.self::V4_PREFIX.')?'.$resourcePrefix.'([1-9][0-9]*)$#', $iri, $matches) !== 1
        ) {
            throw new InvalidIRIResourceException(400,
                'Invalid resource IRI format specified.'
                .' Please use this format: "'.$resourcePrefix.'{id}"'
            );
        }

        return (int)$matches[2];
    }

    /**
     * @param string $resourcePrefix
     * @param array $resources
     * @param string|null $subpropertyName
     * @return array
     */
    public function extractIRIs(string $resourcePrefix, array $resources, string $subpropertyName = null): array
    {
        foreach ($resources as &$resource) {
            $resource = $this->convertResourceIDtoIRI($resourcePrefix, $resource[$subpropertyName]);
        }

        return $resources;
    }

    /**
     * @param string $resourcePrefix
     * @param array $resources
     * @param string|null $subpropertyName
     * @return int[]
     * @throws InvalidIRIResourceException
     */
    public function extractIDs(string $resourcePrefix, array $resources, string $subpropertyName = null): array
    {
        foreach ($resources as &$resource) {
            if (is_numeric($resource) && $resource >= 1) {
                $resource = (int)$resource;
            } else {
                $resource = $this->convertResourceIRItoID($resourcePrefix, $resource);
            }

            if (is_null($subpropertyName)) {
                continue;
            }

            $resource = [
                $subpropertyName => $resource
            ];
        }

        return $resources;
    }
}
