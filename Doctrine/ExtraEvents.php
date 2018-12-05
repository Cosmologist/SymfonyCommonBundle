<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Doctrine;

final class ExtraEvents
{
    /**
     * Private constructor. This class cannot be instantiated.
     */
    private function __construct()
    {
    }

    const postCommit = 'postCommit';
}