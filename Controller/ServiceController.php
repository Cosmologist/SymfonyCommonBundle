<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Calls DI services via HTTP
 */
class ServiceController
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
     * ServiceController constructor.
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
     * Calls the specified service
     *
     * Pass the service arguments through POST-parameters.
     * If the service expects the entity as argument (type-hint exists) - pass the entity identifier instead,
     * the suitable entity will be loaded automatically (Doctrine is used, but you can use the custom loader in the future).
     *
     * @param Request $request Request
     * @param string  $service Service identifier
     * @param string  $method  Service method
     *
     * @return Response|JsonResponse Simple response for scalar results and JSON for other
     */
    public function callServiceAction(Request $request, string $service, string $method): Response
    {
        if (null === $service = $this->container->get($service, ContainerInterface::NULL_ON_INVALID_REFERENCE)) {
            throw new NotFoundHttpException('Service not found');
        }

        if (!method_exists($service, $method)) {
            throw new BadRequestHttpException('Unknown method');
        }

        $arguments = [];
        foreach ((new ReflectionMethod($service, $method))->getParameters() as $parameter) {
            $name = $parameter->getName();

            if ($request->request->has($name)) {
                $value = $request->request->get($name);
            } elseif ($parameter->isOptional()) {
                $value = $parameter->getDefaultValue();
            } else {
                throw new BadRequestHttpException("Argument '$name' expected");
            }

            if (null !== $parameterClass = $parameter->getClass()) {
                if (null === $entityManager = $this->registry->getManagerForClass($parameterClass->getName())) {
                    throw new BadRequestHttpException("Unsupported argument '$name'");
                }

                if (null === $entity = $entityManager->find($parameterClass->getName(), $value)) {
                    throw new NotFoundHttpException("$parameterClass::$value not found");
                }

                $value = $entity;
            }

            $arguments[] = $value;
        }

        $result = $service->$method(...$arguments);

        if (is_scalar($result)) {
            return new Response($result);
        }

        return new JsonResponse($result);
    }
}