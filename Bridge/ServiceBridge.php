<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Bridge;

use Doctrine\Bundle\DoctrineBundle\Registry;
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
     * @var Registry
     */
    private $registry;

    /**
     * ServiceBridgeController constructor.
     *
     * @param ContainerInterface $container Service container
     * @param Registry           $registry  Doctrine registry
     */
    public function __construct(ContainerInterface $container, Registry $registry)
    {
        $this->container = $container;
        $this->registry  = $registry;
    }


    /**
     * @param string $service
     * @param string $method
     * @param array $args
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

            if (in_array($name, $args)) {
                $value = $args[$name];
            } elseif ($parameter->isOptional()) {
                $value = $parameter->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Argument '$name' expected");
            }

            if (null !== $parameterClass = $parameter->getClass()) {
                if (null === $entityManager = $this->registry->getManagerForClass($parameterClass->getName())) {
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