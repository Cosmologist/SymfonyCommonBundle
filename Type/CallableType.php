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
        if (!self::isCompositeFormat($expression)) {
            return parent::parse($expression);
        }

        list($id, $method) = self::parseComposite($expression);

        return [ContainerStatic::get($id), $method];
    }

    /**
     * {@inheritDoc}
     */
    public static function validate(string $expression): bool
    {
        list($id, $method) = self::parseComposite($expression);

        return self::isCompositeFormat($expression) && ContainerStatic::has($id) && method_exists(ContainerStatic::get($id), $method) ? true : parent::validate(
            $expression
        );
    }
}