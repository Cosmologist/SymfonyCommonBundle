<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

use Cosmologist\Bundle\SymfonyCommonBundle\Exception\DoctrineUtilsException;
use Cosmologist\Gears\ObjectType;
use Cosmologist\Gears\StringType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClasUtils;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Doctrine\ORM\Query\Expr\Join;
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
        // Old versions of Doctrine
        if (class_exists(ClassUtils::class)) {
            return ClassUtils::getRealClass(ObjectType::toClassName($target));
        }

        return (new DefaultProxyClassNameResolver())->resolveClassName(ObjectType::toClassName($target));
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
     * Add a join to the query with a support of nested join (ie "contact.user.type")
     *
     * <code>
     * $qb = $entityManager->getRepository(Company::class)->createQueryBuilder('company');
     *
     * DoctrineUtils::joinRecursive($qb, 'contact.user.type');
     * // equivalent to
     * $qb
     *   ->join('company.contact', 'contact')
     *   ->join('contact.user', 'user')
     *   ->join('user.type', 'type');
     * </code>
     *
     * Attention: method doesn't care about alias uniqueness
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $join
     */
    public static function joinRecursive(QueryBuilder $queryBuilder, string $join, string $joinTo = null)
    {
        // Join doesn't required
        if (!StringType::contains($join, '.')) {
            return;
        }

        $joinTo = $joinToAlias ?? current($queryBuilder->getRootAliases());

        [$current, $left] = explode('.', $join, 2);
        $joinCurrent = sprintf('%s.%s', $joinTo, $current);

        $joinCurrentAlias = self::joinOnce($queryBuilder, $joinCurrent, $current);

        return self::joinRecursive($queryBuilder, $left, $joinCurrentAlias);
    }

    /**
     * Add a join to the query once
     *
     * <code>
     * // Adds join and returns an alias of added join
     * DoctrineUtils::joinOnce($qb, 'contact.user', 'u1'); // "u1"
     *
     * // If a join with specified parameters exists then only returns an alias of existed join
     * DoctrineUtils::joinOnce($qb, 'contact.user', 'u2'); // "u1"
     * </code>
     *
     * See arguments description at the {@link QueryBuilder::add()}.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $join
     * @param string       $alias
     * @param string|null  $conditionType
     * @param string|null  $condition
     * @param string|null  $indexBy
     *
     * @return string Alias of existed join or $alias
     */
    public static function joinOnce(QueryBuilder $queryBuilder, string $join, string $alias, string $conditionType = null, string $condition = null, string $indexBy = null): string
    {
        if (null !== $existedJoinAlias = self::getJoinAlias($queryBuilder, $join, $conditionType, $condition, $indexBy)) {
            return $existedJoinAlias;
        }

        $queryBuilder->join($join, $alias, $conditionType, $condition, $indexBy);

        return $alias;
    }

    /**
     * Return alias of a join with specified parameters if exists
     *
     * See arguments description at the {@link QueryBuilder::add()}.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $join
     * @param string|null  $conditionType
     * @param string|null  $condition
     * @param string|null  $indexBy
     *
     * @return string|null
     */
    private static function getJoinAlias(QueryBuilder $queryBuilder, string $join, string $conditionType = null, string $condition = null, string $indexBy = null): ?string
    {
        $joinTo = StringType::strBefore($join, '.');

        foreach ($queryBuilder->getDQLPart('join') as $dqlJoinsTo => $dqlJoins) {
            if ($dqlJoinsTo === $joinTo) {
                /** @var Join $dqlJoin */
                foreach ($dqlJoins as $dqlJoin) {
                    $joinTypeEqual      = $dqlJoin->getJoinType() === Join::INNER_JOIN;
                    $joinEqual          = $dqlJoin->getJoin() === $join;
                    $conditionTypeEqual = $dqlJoin->getConditionType() === $conditionType;
                    $conditionEqual     = $dqlJoin->getCondition() == $condition;
                    $indexByEqual       = $dqlJoin->getIndexBy() === $indexBy;

                    if ($joinTypeEqual && $joinEqual && $conditionTypeEqual && $conditionEqual && $indexByEqual) {
                        return $dqlJoin->getAlias();
                    }
                }
            }
        }

        return null;
    }

    /**
     * Merge multiple Doctrine\Common\Collections\Criteria into a one Doctrine\Common\Collections\Criteria
     *
     * @param Criteria $firstCriteria
     * @param Criteria $secondCriteria
     * @param Criteria ...$oneOrMoreCriteria
     *
     * @return Criteria
     */
    public static function mergeCriteria(Criteria $firstCriteria, Criteria $secondCriteria, Criteria ...$oneOrMoreCriteria): Criteria
    {
        array_unshift($oneOrMoreCriteria, $firstCriteria, $secondCriteria);

        $resultCriteria = new Criteria();

        foreach ($oneOrMoreCriteria as $criteria) {
            $resultCriteria->andWhere($criteria->getWhereExpression());
        }

        return $resultCriteria;
    }
}
