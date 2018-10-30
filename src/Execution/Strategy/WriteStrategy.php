<?php

namespace Digia\GraphQL\Execution\Strategy;

use React\Promise\PromiseInterface;

/**
 * Implements the "Evaluating selection sets" section of the spec
 * for "write" mode.
 *
 * Class WriteStrategy
 * @package Digia\GraphQL\Execution\Strategy
 */
class WriteStrategy extends AbstractExecutionStrategy
{
    /**
     * @inheritdoc
     */
    public function execute(): PromiseInterface
    {
    }
}
