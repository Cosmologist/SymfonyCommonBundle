<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Type;

use Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection\ContainerStatic;
use Cosmologist\Gears\CallableType as GearsCallableType;

class CallableType extends GearsCallableType
{
    /**
     * {@inheritDoc}
     */
    public static function parse(string $expression): callable
    {
        if (self::validateServiceCallable($expression) === false) {
            return parent::parse($expression);
        }

        [$id, $method] = self::parseComposite($expression);

        return [ContainerStatic::get($id), $method];
    }

    /**
     * {@inheritDoc}
     */
    public static function validate(string $expression): bool
    {
        return self::validateServiceCallable($expression) || parent::validate($expression);
    }

    /**
     * @param string $expression
     *
     * @return bool
     */
    private static function validateServiceCallable(string $expression)
    {
        [$id, $method] = self::parseComposite($expression);

        return self::isCompositeFormat($expression) && ContainerStatic::has($id) && method_exists(ContainerStatic::get($id), $method);
    }
}