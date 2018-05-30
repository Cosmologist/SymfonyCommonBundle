<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection;

use AppCache;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Static access to the service container
 */
class ContainerStatic
{
    /**
     * Instance of container.
     *
     * @var null|ContainerInterface
     */
    protected static $container = null;

    /**
     * Set the service container.
     *
     * @param ContainerInterface The service container
     */
    static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }

    /**
     * Returns the service container.
     *
     * @return ContainerInterface|null The service container
     */
    static function getContainer(): ?ContainerInterface
    {
        return self::$container;
    }

    /**
     * Gets a service.
     *
     * @param string $id              The service identifier
     * @param int    $invalidBehavior The behavior when the service does not exist
     *
     * @return object The associated service
     *
     * @throws ServiceCircularReferenceException When a circular reference is detected
     * @throws ServiceNotFoundException          When the service is not defined
     *
     * @see Reference
     */
    static function get(string $id)
    {
        return self::$container->get($id);
    }

    /**
     * Gets a parameter.
     *
     * @param string $name The parameter name
     *
     * @return mixed The parameter value
     *
     * @throws InvalidArgumentException if the parameter is not defined
     */
    static function getParameter(string $name)
    {
        return self::$container->getParameter($name);
    }
}