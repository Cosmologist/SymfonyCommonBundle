<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection;

class ConfigurationUtils
{
    /**
     * Sets the attribute that should hold the node key.
     *
     * Useful for:
     * - to simplify your configuration
     * - to avoid problem of losing the key when you merge config across files
     *
     * @see https://github.com/symfony/symfony/issues/29817
     *
     * Config like this:
     * ```
     * something:
     *   servers:
     *      serverA:
     *        username: userA
     *        password: passwordA
     *      serverB:
     *        username: userB
     *        password: passwordB
     * ```
     * comes out like:
     * ```
     * servers [
     *    serverA => [username: userA, password: passwordA, server: serverA],
     *    serverB => [username: userB, password: passwordB, server: serverB],
     *  ]
     * ```
     *
     * Usage:
     * ```php
     * ...
     * ->arrayNode('events')
     *     ->beforeNormalization()
     *         ->always($useKeyAsAttribute)
     *     ->end()
     *     ->prototype('array')
     * ...
     * ```
     *
     * @param string $attribute The attribute name
     * @param bool   $overwrite Overwrite existing attribute?
     *
     * @return \Closure
     */
    public static function useKeyAsAttribute(string $attribute, bool $overwrite = false)
    {
        return function ($map) use ($attribute, $overwrite) {
            foreach ($map as $key => $values) {
                if (array_key_exists($key, $values) && !$overwrite) {
                    continue;
                }

                $map[$key][$attribute] = $key;
            }

            return $map;
        };
    }
}