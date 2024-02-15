<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Bridge;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * A convenient way to dynamically access symfony services.
 *
 * @see ServiceBridgeController
 */
class ServiceBridge
{
    /**
     * Service container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Doctrine registry
     *
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param ContainerInterface $container Service container
     * @param ManagerRegistry    $doctrine  Doctrine registry
     */
    public function __construct(ContainerInterface $container, ManagerRegistry $doctrine)
    {
        $this->container = $container;
        $this->doctrine  = $doctrine;
    }


    /**
     * @param string $service
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     *
     * @throws ServiceNotFoundException If service does not exist
     * @throws ReflectionException if method does not exist
     */
    public function call(string $service, string $method, array $args)
    {
        $service = $this->container->get($service, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE);

        $arguments = [];
        foreach ((new ReflectionMethod($service, $method))->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $args)) {
                $value = $args[$name];
            } elseif ($parameter->isOptional()) {
                $value = $parameter->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Argument '$name' expected");
            }

            if (null !== $parameterClass = $parameter->getClass()) {
                if (null === $entityManager = $this->doctrine->getManagerForClass($parameterClass->getName())) {
                    throw new InvalidArgumentException("Unsupported argument '$name'");
                }

                if (null === $entity = $entityManager->find($parameterClass->getName(), $value)) {
                    throw new InvalidArgumentException("$parameterClass::$value not found");
                }

                $value = $entity;
            }

            $arguments[] = $value;
        }

        return $result = $service->$method(...$arguments);
    }
}
