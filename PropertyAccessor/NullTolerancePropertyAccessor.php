<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\PropertyAccessor;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class NullTolerancePropertyAccessor extends PropertyAccessor
{
    /**
     * {@inheritdoc}
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        return parent::isReadable($objectOrArray, $propertyPath) ? parent::getValue($objectOrArray, $propertyPath) : null;
    }

}