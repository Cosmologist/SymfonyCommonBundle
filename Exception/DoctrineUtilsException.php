<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Exception;

use RuntimeException;

class DoctrineUtilsException extends RuntimeException implements SymfonyCommonExceptionInterface
{
    /**
     * Unsupported entity primary key
     *
     * @param string $fqcn FQCN
     *
     * @return $this
     */
    public static function unsupportedPrimaryKey($fqcn)
    {
        return new self("Unsupported '$fqcn' primary key, supports only entities with single field primary key.");
    }
}
