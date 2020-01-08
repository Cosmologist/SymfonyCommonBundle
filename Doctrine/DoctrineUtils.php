<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

use Cosmologist\Bundle\SymfonyCommonBundle\Exception\DoctrineUtilsException;
use Cosmologist\Gears\ObjectType;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;

class DoctrineUtils
{
    /**
     * Doctrine
     *
     * @var Registry
     */
    private $doctrine;

    /**
     * CommonUtils constructor.
     *
     * @param Registry $doctrine Doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get entity real class
     *
     * @param string|object $target FQCN or object
     *
     * @return string FQCN
     */
    public function getRealClass($target)
    {
        return ClassUtils::getRealClass(ObjectType::toClassName($target));
    }

    /**
     * Get doctrine class metadata
     *
     * @param object|string $entity Entity object or FQCN
     *
     * @return ClassMetadata
     */
    public function getClassMetadata($entity)
    {
        $fqcn = $this->getRealClass($entity);
        if (null === $entityManager = $this->doctrine->getManagerForClass($fqcn)) {
            throw DoctrineUtilsException::unsupportedClass($fqcn);
        }

        return $entityManager->getClassMetadata($fqcn);
    }

    /**
     * Get entity identifier field
     *
     * If entity implements multiple identifiers then throw exception
     *
     * @param object|string $entity Entity object or FQCN
     *
     * @return mixed
     */
    public function getEntitySingleIdentifierField($entity)
    {
        if (null === $entity) {
            return null;
        }

        $metadata             = $this->getClassMetadata($entity);
        $identifierFieldNames = $metadata->getIdentifierFieldNames();

        if (count($identifierFieldNames) !== 1) {
            throw DoctrineUtilsException::unsupportedPrimaryKey($metadata->getName());
        }

        return current($identifierFieldNames);
    }

    /**
     * Get entity identifier value
     *
     * If entity implements multiple identifiers then throw exception
     *
     * @param object $entity Entity
     *
     * @return mixed
     */
    public function getEntitySingleIdentifierValue($entity)
    {
        if (null === $entity) {
            return null;
        }

        $metadata    = $this->getClassMetadata($entity);
        $identifiers = $metadata->getIdentifierValues($entity);

        if (count($identifiers) !== 1) {
            throw DoctrineUtilsException::unsupportedPrimaryKey($metadata->getName());
        }

        return current($identifiers);
    }

    /**
     * Dump entity data to array
     *
     * @param object $entity Entity
     *
     * @return array
     */
    public function dump($entity): array
    {
        $dump = [];

        foreach ($this->getClassMetadata($entity)->reflFields as $name => $refProp) {
            $dump[$name] = $refProp->getValue($entity);
        }

        return $dump;
    }
}