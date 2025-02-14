<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\PropertyAccessor;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class NullTolerancePropertyAccessor extends PropertyAccessor
{
    public function getValue(object|array $objectOrArray, string|PropertyPathInterface $propertyPath): mixed
    {
        return parent::isReadable($objectOrArray, $propertyPath) ? parent::getValue($objectOrArray, $propertyPath) : null;
    }
}
