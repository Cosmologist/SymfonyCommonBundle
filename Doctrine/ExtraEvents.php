<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

/**
 * Connection extra-events enum
 */
final class ExtraEvents
{
    /**
     * Private constructor. This class cannot be instantiated.
     */
    private function __construct()
    {
    }

    const postBeginTransaction = 'postBeginTransaction';
    const postCommit = 'postCommit';
    const postRollback = 'postRollback';
}