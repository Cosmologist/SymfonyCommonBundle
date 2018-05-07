<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Static access to the service container
 */
class ContainerStatic
{
    /**
     * Gets the current container.
     *
     * @return ContainerInterface A ContainerInterface instance
     */
    static function getContainer(): ContainerInterface
    {
        global $kernel;

        return $kernel->getContainer();
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
        return self::getContainer()->get($id);
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
        return self::getContainer()->getParameter($name);
    }
}