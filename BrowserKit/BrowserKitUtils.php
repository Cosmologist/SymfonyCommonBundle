<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\BrowserKit;

use Symfony;

class BrowserKitUtils
{
    /**
     * Adds the specified HTTP header to the prepared request
     *
     * @param Symfony\Component\BrowserKit\Client $client
     * @param string                              $name
     * @param                                     $value
     */
    public static function addHeader(Symfony\Component\BrowserKit\Client $client, string $name, $value)
    {
        $client->setServerParameter('http-' . $name, $value);
    }
}