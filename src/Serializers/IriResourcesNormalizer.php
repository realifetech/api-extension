<?php

namespace RL\Serializers;

use Doctrine\Common\Annotations\Reader;
use RL\Annotation\IriResource;
use RL\Exception\InvalidIRIResourceException;
use RL\Routing\IriConverter;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use \Exception;

class IriResourcesNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    /** @var NormalizerInterface */
    private NormalizerInterface $normalizer;

    /** @var Reader */
    private Reader $annotationReader;

    /** @var IriConverter */
    private IriConverter $iriConverter;

    public function __construct(NormalizerInterface $normalizer, Reader $annotationReader, IriConverter $iriConverter)
    {
        $this->normalizer       = $normalizer;
        $this->annotationReader = $annotationReader;
        $this->iriConverter     = $iriConverter;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $annotations = $this->getPropertyAnnotations(get_class($object));

        $normalised = $this->normalizer->normalize($object, $format, $context);

        foreach ($annotations as $field => $annotation) {
            if (!empty($normalised[$field])) {
                $normalised[$field] = $this->normalizePropertyValue(
                    $this->normalizeResourcePrefix($annotation->resourcePrefix),
                    $normalised[$field],
                    $annotation->subpropertyName ?? null
                );
            }
        }

        return $normalised;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $annotations = $this->getPropertyAnnotations($type);

        foreach ($annotations as $property => $annotation) {
            if (!empty($data[$property])) {
                $data[$property] = $this->denormalizePropertyValue(
                    $this->normalizeResourcePrefix($annotation->resourcePrefix),
                    $data[$property],
                    $annotation->crossMicroservice,
                    $annotation->subpropertyName ?? null
                );
            }
        }

        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param string $resourcePrefix
     * @return string
     */
    private function normalizeResourcePrefix(string $resourcePrefix): string
    {
        return rtrim($resourcePrefix, '/') .'/';
    }

    /**
     * @param string|null $subpropertyName
     * @throws Exception
     */
    private function ensureSubpropertyNameIsDefined(string $subpropertyName = null): void
    {
        if (empty($subpropertyName)) {
            throw new Exception('@IRIResource subpropertyName has to be defined for resource arrays');
        }
    }

    /**
     * @param string $resourcePrefix
     * @param int|string|array $propertyValue
     * @param string|null $subpropertyName
     * @return array|string
     * @throws Exception
     */
    private function normalizePropertyValue(
        string $resourcePrefix,
        $propertyValue,
        string $subpropertyName = null
    ) {
        if (is_array($propertyValue)) {
            $this->ensureSubpropertyNameIsDefined($subpropertyName);
            return $this->iriConverter->extractIRIs($resourcePrefix, $propertyValue, $subpropertyName);
        }

        if (!is_numeric($propertyValue)) {
            //apparently it's an IRI already
            return $propertyValue;
        }

        return $this->iriConverter->convertResourceIDtoIRI($resourcePrefix, $propertyValue);
    }

    /**
     * @param string $resourcePrefix
     * @param int|string|array $propertyValue
     * @param bool $crossMicroservice
     * @param string|null $subpropertyName
     * @return array|string
     * @throws InvalidIRIResourceException
     * @throws Exception
     */
    private function denormalizePropertyValue(
        string $resourcePrefix,
        $propertyValue,
        bool $crossMicroservice,
        string $subpropertyName = null
    ) {
        if (is_array($propertyValue)) {
            $this->ensureSubpropertyNameIsDefined($subpropertyName);
            return $this->iriConverter->extractIDs($resourcePrefix, $propertyValue, $subpropertyName);
        }

        if ($crossMicroservice) {
            return $this->iriConverter->convertResourceIRItoID($resourcePrefix, $propertyValue);
        }

        return $this->iriConverter->addPrefix($propertyValue);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        if (!is_object($data)) {
            return false;
        }

        $propertyAnnotations = $this->getPropertyAnnotations(get_class($data));

        return !empty($propertyAnnotations);
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        $propertyAnnotations = $this->getPropertyAnnotations($type);

        return !empty($propertyAnnotations);
    }

    /**
     * @param string $type
     * @return IriResource[]
     */
    protected function getPropertyAnnotations(string $type): array
    {
        try {
            $reflection = new \ReflectionClass($type);

            $propertyAnnotations = [];

            $properties = $reflection->getProperties();

            foreach ($properties as $property) {

                if ($annotation = $this->annotationReader->getPropertyAnnotation($property, IriResource::class)) {
                    $propertyAnnotations[$property->name] = $annotation;
                }
            }
        } catch (\ReflectionException $e) {
            return [];
        }

        return $propertyAnnotations;
    }
}
