<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Type;

use Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection\ContainerStatic;
use Cosmologist\Gears\CallableType as GearsCallableType;

class CallableType extends GearsCallableType
{
    /**
     * Creates and returns callable from a string expression
     *
     * Supports reference to container services:
     * - 'foo.bar::baz' - returns the callable for the service identified "foo.bar" (Symfony 2.x style) and the "baz" method
     * - 'Foo\Bar::baz' - returns the callable for the service identified "Foo\Bar" (Symfony 3.x style) and method "baz"
     *
     * @return callable
     */
    public static function toCallable(string $expression): callable
    {
        if (parent::validate($expression)) {
            return $expression;
        }

        // It uses closure to avoid initializing unnecessary services (when calling Container::get)
        return function (...$args) use ($expression) {
            return call_user_func_array([ContainerStatic::get(self::extractClassFromExpression($expression)), self::extractMethodFromExpression($expression)], $args);
        };
    }

    /**
     * {@inheritDoc}
     */
    public static function validate(string $expression): bool
    {
        return parent::validate($expression) || (self::isCompositeFormat($expression) && ContainerStatic::has(self::extractClassFromExpression($expression)));
    }
}