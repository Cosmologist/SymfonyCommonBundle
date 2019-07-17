<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Twig;

use Cosmologist\Bundle\SymfonyCommonBundle\Type\CallableType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The extension provides various callable objects (functions, method of static classes methods, methods of container services) to Twig templates.
 */
class PhpExtension extends AbstractExtension
{
    /**
     * @var array
     */
    private $functions;

    /**
     * @var array
     */
    private $filters;

    /**
     * @param array $functions
     * @param array $filters
     */
    public function __construct(array $functions, array $filters)
    {
        $this->functions = $functions;
        $this->filters   = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return $this->prepare($this->filters, TwigFilter::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->prepare($this->functions, TwigFunction::class);
    }

    /**
     * Prepares callable objects defined in the application config for Twig
     *
     * @param array  $functions
     * @param string $twigCallableClass
     *
     * @return array
     */
    protected function prepare(array $functions, string $twigCallableClass): array
    {
        return
            array_map(
                function ($expression, $name) use ($twigCallableClass) {
                    return new $twigCallableClass($name, CallableType::toCallable($expression));
                },
                $functions,
                array_keys($functions)
            );
    }
}