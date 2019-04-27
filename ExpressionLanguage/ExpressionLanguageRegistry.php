<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\ExpressionLanguage;

use RuntimeException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionLanguageRegistry
{
    /**
     * Map between ExpressionLanguage preset and functions for it
     *
     * @var callable[]
     */
    protected $functions = [];

    /**
     * @param string     $name      Preset name.
     * @param callable[] $functions Preset functions.
     *
     */
    public function set(string $name, array $functions)
    {
        $this->functions[$name] = $functions;
    }


    /**
     * Create ExpressionLanguage and inititlize it with preset functions
     *
     * @param string $name
     *
     * @return ExpressionLanguage
     */
    public function get(string $name): ExpressionLanguage
    {
        if (!array_key_exists($name, $this->functions)) {
            throw new RuntimeException("ExpressionLanguage preset '$name' not found");
        }

        return new ExpressionLanguage(null, new PresetFunctionProvider($this->functions[$name]));
    }
}