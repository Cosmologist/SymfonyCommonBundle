<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Routing utils
 */
class RoutingUtils
{
    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;

    /**
     * Сonstructor
     *
     * @param HttpKernelInterface $httpKernel
     */
    public function __construct(HttpKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    /**
     * Forwards to another URI
     *
     * Like Symfony\Bundle\FrameworkBundle\Controller\Controller::forward, but using URI
     *
     * @param string $uri The URI
     *
     * @return Response
     */
    public function forwardToUri(string $uri): Response
    {
        return $this->httpKernel->handle(Request::create($uri), HttpKernelInterface::SUB_REQUEST);
    }
}