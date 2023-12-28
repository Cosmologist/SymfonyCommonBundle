<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

use Cosmologist\Bundle\SymfonyCommonBundle\Exception\DoctrineUtilsException;
use Cosmologist\Gears\ObjectType;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;

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
     * @return ClassMetadata|null
     */
    public function getClassMetadata($entity)
    {
        $fqcn = $this->getRealClass($entity);

        if (null === $entityManager = $this->doctrine->getManagerForClass($fqcn)) {
            return null;
        }

        return $entityManager->getClassMetadata($fqcn);
    }

    /**
     * @deprecated Use UnitOfWork::getEntityIdentifier instead
     *
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
     * Determine if the object or FQCN is a Doctrine entity (under Doctrine control) or not
     *
     * @param object|string $entity Entity object or FQCN
     *
     * @return bool
     */
    public function isEntity($entity)
    {
        return null !== $this->doctrine->getManagerForClass($this->getRealClass($entity));
    }

    /**
     * Compute a query results count
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return int|mixed|string
     */
    public function getQueryResultCount(QueryBuilder $queryBuilder)
    {
        $queryBuilderCount = clone $queryBuilder;

        if ($queryBuilderCount->getDQLPart('orderBy')) {
            $queryBuilderCount->resetDQLPart('orderBy');
        }

        $fromEntity = current($queryBuilderCount->getDQLPart('from'))->getFrom();

        $queryBuilderCount->select(sprintf(
            'count(DISTINCT %s.%s) as cnt',
            current($queryBuilderCount->getRootAliases()),
            $this->getEntitySingleIdentifierField($fromEntity)
        ));

        return $queryBuilderCount->getQuery()->getSingleScalarResult();
    }

    /**
     * Get the readable alias for the doctrine entity
     *
     * <strong>Example:</strong>
     * <code>
     * $this->getEntityAlias(FooBundle\Entity\Bar\Baz::class); // 'foo.bar.baz'
     * $this->decodeEntityAlias('foo.bar.baz'); // 'FooBundle\Entity\Bar\Baz::class'
     * </code>
     *
     * Use the {@link DoctrineUtils::decodeEntityAlias()} to backward transformation.
     *
     * @param string $fqcn The Doctrine entity FQCN
     *
     * @return string
     */
    public static function getEntityAlias(string $fqcn)
    {
        $transform = str_replace(['\\Entity\\', 'Bundle\\'], '\\', trim($fqcn, '\\'));

        return implode('.', array_map('lcfirst', explode('\\', $transform)));
    }

    /**
     * Get the doctrine entity FQCN from the entity alias (created with {@link DoctrineUtils::getEntityAlias()})
     *
     * @param string $alias The Doctrine entity alias (from {@link DoctrineUtils::getEntityAlias()})
     *
     * @return string|null
     */
    public function decodeEntityAlias(string $alias)
    {
        foreach ($this->doctrine->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                if ($alias === self::getEntityAlias($metadata->getReflectionClass()->getName())) {
                    return $metadata->getReflectionClass()->getName();
                }
            }
        }

        return null;
    }
}
