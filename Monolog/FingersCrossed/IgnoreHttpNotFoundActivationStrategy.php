<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\Monolog\FingersCrossed;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Logger;
use Monolog\LogRecord;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Monolog activation strategy for Symfony 3.x to skip the 404 HttpException records.
 *
 * Monolog NotFoundActivationStrategy (activation_strategy, excluded_404s and excluded_http_codes options) does not work in Symfony 3.0 as currently monolog-bundle injects a reference to request from the service container into the NotFoundActivationStrategy.
 *
 * @see https://github.com/symfony/monolog-bundle/issues/166#issuecomment-221725696
 *
 * @todo Other HTTP-codes support
 * @todo Configure default actionLevel value via Configuration
 */
class IgnoreHttpNotFoundActivationStrategy extends ErrorLevelActivationStrategy
{
    /**
     * IgnoreHttpNotFoundActivationStrategy constructor.
     *
     * @param int $actionLevel Record level (see Logger constants)
     */
    public function __construct($actionLevel = Logger::WARNING)
    {
        parent::__construct($actionLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function isHandlerActivated(LogRecord $record): bool
    {
        $isActivated = parent::isHandlerActivated($record);

        if ($isActivated && isset($record['context']['exception'])
            && $record['context']['exception'] instanceof HttpException
            && 404 === $record['context']['exception']->getStatusCode()
        ) {
            return false;
        }

        return $isActivated;
    }
}
