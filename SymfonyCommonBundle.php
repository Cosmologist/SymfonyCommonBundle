<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle;

use Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection\ContainerStatic;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SymfonyCommonBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        ContainerStatic::setContainer($this->container);
    }

}
