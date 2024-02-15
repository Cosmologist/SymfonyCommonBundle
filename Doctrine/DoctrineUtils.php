<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

use Cosmologist\Bundle\SymfonyCommonBundle\Exception\DoctrineUtilsException;
use Cosmologist\Gears\ObjectType;
use Cosmologist\Gears\StringType;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineUtils
{
    /**
     * Doctrine
     *
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine Doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get entity real class
     *
     * @param string|object $target The entity object or FQCN
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
     * @param object|string $entity The entity object or FQCN
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
     * Get the target class name of the given association path (ie "contact.user") recursively
     *
     * <code>
     * $doctrineUtils->getAssociationTargetClassRecursive('AppBundle/Entity/Company', 'contact.user'); // 'AppBundle/Entity/User'
     * </code>
     *
     * @param object|string $entity The entity object or FQCN
     * @param string        $path   The association path, ie "contact.user"
     *
     * @return string
     */
    public function getAssociationTargetClassRecursive($entity, string $path)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->getClassMetadata($entity);

        if (!StringType::contains($path, '.')) {
            return $metadata->getAssociationTargetClass($path);
        }

        list($current, $left) = explode('.', $path, 2);

        return $this->getAssociationTargetClassRecursive($metadata->getAssociationTargetClass($current), $left);
    }

    /**
     * @param object|string $entity Entity object or FQCN
     *
     * @return mixed
     * @deprecated Use UnitOfWork::getEntityIdentifier instead
     *
     * Get entity identifier field
     *
     * If entity implements multiple identifiers then throw exception
     *
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

    /**
     * Perform recursively join operation of the given association path (ie "contact.user.type")
     *
     * <code>
     * $qb = $entityManager->getRepository(Company::class)->createQueryBuilder('company');
     *
     * # Recursive joins
     * DoctrineUtils::joinRecursive($qb, 'contact.user.type'); // ["user", "type"]
     * // equivalent to
     * $qb
     *   ->join('company.contact', 'contact')
     *   ->join('contact.user', 'user')
     *   ->join('user.type', 'type');
     *
     * # Join doesn't required
     * DoctrineUtils::joinRecursive($qb, 'contact'); // ["company", "contact"]
     * </code>
     *
     * Attention: method doesn't care about alias uniqueness or join doubling
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $path
     * @param string|null  $joinToAlias
     *
     * @return array<string, string> Touple of the last relationship alias to join and the alias of the join.
     */
    public static function joinRecursive(QueryBuilder $queryBuilder, string $path, string $joinToAlias = null)
    {
        $joinToAlias = $joinToAlias ?? current($queryBuilder->getRootAliases());

        if (!StringType::contains($path, '.')) {
            return [$joinToAlias, $path];
        }

        list($current, $left) = explode('.', $path, 2);
        $queryBuilder->join(sprintf('%s.%s', $joinToAlias, $current), $current);

        return self::joinRecursive($queryBuilder, $left, $current);
    }
}
