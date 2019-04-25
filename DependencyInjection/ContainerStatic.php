<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Static access to the service container
 */
class ContainerStatic
{
    /**
     * Container
     *
     * @var ContainerInterface
     */
    protected static $container;

    /**
     * Set the service container
     *
     * @internal
     *
     * @param ContainerInterface $container The service container
     */
    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }

    /**
     * Returns the service container
     *
     * @return ContainerInterface The service container
     */
    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            throw new RuntimeException('ContainerStatic is not initialized. Have you added the symfonyCommonBundle in AppKernel.php?');
        }
        return self::$container;
    }

    /**
     * Gets a service statically
     *
     * @param string $id The service identifier
     *
     * @return object The associated service
     *
     * @see ContainerInterface::get
     */
    public static function get(string $id)
    {
        return self::$container->get($id);
    }

    /**
     * Checks if a service exists statically
     *
     * @param string $id The service identifier
     *
     * @return bool
     *
     * @see ContainerInterface::has
     */
    public static function has(string $id): bool
    {
        return self::$container->has($id);
    }

    /**
     * Gets a parameter statically
     *
     * @param string $name The parameter name
     *
     * @return mixed The parameter value
     *
     * @see ContainerInterface::getParameter()
     */
    public static function getParameter(string $name)
    {
        return self::$container->getParameter($name);
    }
}