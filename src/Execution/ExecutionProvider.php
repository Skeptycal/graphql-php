<?php

namespace Digia\GraphQL\Execution;

use Digia\GraphQL\Execution\Strategy\ReadStrategy;
use Digia\GraphQL\Execution\Strategy\WriteStrategy;
use League\Container\ServiceProvider\AbstractServiceProvider;

class ExecutionProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        ExecutionInterface::class,
        ValuesHelper::class,
    ];

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->container->share(ExecutionInterface::class, ExecutionWithStrategies::class);
        $this->container->share(ValuesHelper::class, ValuesHelper::class);
    }
}
