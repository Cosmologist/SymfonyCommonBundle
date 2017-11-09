<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Twig;

use Twig_Extension;

/**
 * Extension brings pre-configured list of functions and static class methods into Twig as filters and functions
 */
class PhpExtension extends Twig_Extension
{
    /**
     * @var array
     */
    private $availableFunctions;

    /**
     * @var array
     */
    private $availableFilters;

    /**
     * @param array $availableFunctions
     * @param array $availableFilters
     */
    public function __construct(array $availableFunctions, array $availableFilters)
    {
        $this->availableFunctions = $availableFunctions;
        $this->availableFilters   = $availableFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $callbacks = $this->getCallbacks($this->availableFilters);

        return
            \array_map(
                function ($function, $callback) {
                    return new \Twig_SimpleFilter($function, $callback);
                },
                \array_keys($callbacks), $callbacks
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $callbacks = $this->getCallbacks($this->availableFunctions);

        return
            \array_map(
                function ($function, $callback) {
                    return new \Twig_SimpleFunction($function, $callback);
                },
                \array_keys($callbacks), $callbacks
            );
    }

    /**
     * Build callbacks for callables from configuration
     *
     * @param string|array $callables
     *
     * @return array
     */
    private function getCallbacks($callables)
    {
        $result = array();

        foreach ($callables as $function) {

            if (is_array($function) && !is_numeric(key($function))) {
                $callback = current($function);
                $function = key($function);
            } else {
                $callback = $function;
            }

            $result[$function] = $callback;
        }

        return $result;
    }

    /**
     * {@inheritdoc};
     */
    public function getName()
    {
        return 'cosmologist_twig_php_extension';
    }
}