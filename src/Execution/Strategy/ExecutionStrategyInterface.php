<?php

namespace Digia\GraphQL\Execution\Strategy;

interface ExecutionStrategyInterface
{
    /**
     * @param array $params
     * @return mixed
     */
    public function execute();
}
