<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Type;

use Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection\ContainerStatic;
use Cosmologist\Gears\CallableType as GearsCallableType;
use Cosmologist\Gears\StringType;

class CallableType
{
    /**
     * Parse callable from string expression.
     *
     * Supported syntax:
     * - 'serviceName::method' - callable for Symfony DI service method.
     * - 'My\ClassName::method' - callable for static class method.
     * - 'myFunction' - callable for function.
     *
     * @see Cosmologist\Gears\CallableType::parse
     *
     * @param string $expression The callable expression.
     *
     * @return callable
     */
    public static function parse(string $expression): callable
    {
        if (!StringType::contains($expression, GearsCallableType::SEPARATOR)) {
            return $expression;
        }

        $classOrService = StringType::strBefore($expression, GearsCallableType::SEPARATOR);
        $method         = StringType::strAfter($expression, GearsCallableType::SEPARATOR);

        $container = ContainerStatic::getContainer();

        if ($container->has($classOrService)) {
            $classOrService = $container->get($classOrService);
        }

        return [$classOrService, $method];
    }
}