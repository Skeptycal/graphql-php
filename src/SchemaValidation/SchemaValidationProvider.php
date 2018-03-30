<?php

namespace Digia\GraphQL\SchemaValidation;

use Digia\GraphQL\SchemaValidation\Rule\DirectivesRule;
use Digia\GraphQL\SchemaValidation\Rule\RootTypesRule;
use Digia\GraphQL\SchemaValidation\Rule\TypesRule;
use League\Container\ServiceProvider\AbstractServiceProvider;

class SchemaValidationProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $provides = [
        ValidationContextCreatorInterface::class,
        SchemaValidatorInterface::class,
        RootTypesRule::class,
        DirectivesRule::class,
        TypesRule::class,
    ];

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->container->add(ValidationContextCreatorInterface::class, ValidationContextCreator::class,
            true/* $shared */);
        $this->container->add(SchemaValidatorInterface::class, SchemaValidator::class, true/* $shared */)
            ->withArgument(ValidationContextCreatorInterface::class);

        // Rules
        $this->container->add(RootTypesRule::class, RootTypesRule::class);
        $this->container->add(DirectivesRule::class, DirectivesRule::class);
        $this->container->add(TypesRule::class, TypesRule::class);
    }
}