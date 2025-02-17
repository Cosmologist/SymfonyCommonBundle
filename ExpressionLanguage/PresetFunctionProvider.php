<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class PresetFunctionProvider implements ExpressionFunctionProviderInterface
{

    /**
     * @var callable[]
     */
    private $functions;

    /**
     * @param callable[] $functions
     */
    public function __construct(array $functions)
    {
        $this->functions = $functions;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return array_map([ExpressionFunction::class, 'fromPhp'], $this->functions);
    }
}