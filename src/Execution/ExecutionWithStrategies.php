<?php

namespace Digia\GraphQL\Execution;

use Digia\GraphQL\Error\ErrorHandlerInterface;
use Digia\GraphQL\Execution\Strategy\ExecutionStrategyInterface;
use Digia\GraphQL\Execution\Strategy\ReadStrategy;
use Digia\GraphQL\Execution\Strategy\WriteStrategy;
use Digia\GraphQL\Language\Node\DocumentNode;
use Digia\GraphQL\Language\Node\FragmentDefinitionNode;
use Digia\GraphQL\Language\Node\FragmentSpreadNode;
use Digia\GraphQL\Language\Node\OperationDefinitionNode;
use Digia\GraphQL\Schema\Schema;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\FulfilledPromise;
use React\Promise\Promise;

class ExecutionWithStrategies implements ExecutionInterface
{
    /**
     * @inheritdoc
     */
    public function execute(
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue = null,
        $contextValues = null,
        array $variableValues = [],
        string $operationName = null,
        callable $fieldResolver = null,
        ?ErrorHandlerInterface $errorHandler = null
    ): ExecutionResult {
        try {
            $context = $this->createContext(
                $schema,
                $documentNode,
                $rootValue,
                $contextValues,
                $variableValues,
                $operationName,
                $fieldResolver
            );

            // Return early errors if execution context failed.
            if (!empty($context->getErrors())) {
                return new ExecutionResult(null, $context->getErrors());
            }
        } catch (ExecutionException $error) {
            return new ExecutionResult(null, [$error]);
        }

        $fieldCollector = new FieldCollector($context);

        $strategy = $operationName === 'mutation'
            ? new WriteStrategy($context, $fieldCollector)
            : new ReadStrategy($context, $fieldCollector);

        $result = null;

        try {
            $result = $strategy->execute();
        } catch (ExecutionException $exception) {
            $context->addError($exception);
        } catch (\Throwable $exception) {
            $context->addError(
                new ExecutionException($exception->getMessage(), null, null, null, null, null, $exception)
            );
        }

        if ($result instanceof ExtendedPromiseInterface) {
            $result->then(null, function (ExecutionException $exception) use ($context) {
                $context->addError($exception);
            });
        }

        return new ExecutionResult($result, $context->getErrors());
    }

    /**
     * @param Schema        $schema
     * @param DocumentNode  $documentNode
     * @param mixed         $rootValue
     * @param mixed         $contextValue
     * @param mixed         $rawVariableValues
     * @param null|string   $operationName
     * @param callable|null $fieldResolver
     * @return ExecutionContext
     * @throws ExecutionException
     */
    protected function createContext(
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue,
        $contextValue,
        $rawVariableValues,
        ?string $operationName = null,
        ?callable $fieldResolver = null
    ): ExecutionContext {
        $errors    = [];
        $fragments = [];
        $operation = null;

        foreach ($documentNode->getDefinitions() as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                if (null === $operationName && null !== $operation) {
                    throw new ExecutionException(
                        'Must provide operation name if query contains multiple operations.'
                    );
                }

                if (null === $operationName || $definition->getNameValue() === $operationName) {
                    $operation = $definition;
                }

                continue;
            }

            if ($definition instanceof FragmentDefinitionNode || $definition instanceof FragmentSpreadNode) {
                $fragments[$definition->getNameValue()] = $definition;

                continue;
            }
        }

        if (null === $operation) {
            if (null !== $operationName) {
                throw new ExecutionException(sprintf('Unknown operation named "%s".', $operationName));
            }

            throw new ExecutionException('Must provide an operation.');
        }

        $coercedVariableValues = coerceVariableValues(
            $schema,
            $operation->getVariableDefinitions(),
            $rawVariableValues
        );

        $variableValues = $coercedVariableValues->getValue();

        if ($coercedVariableValues->hasErrors()) {
            $errors = $coercedVariableValues->getErrors();
        }

        return new ExecutionContext(
            $schema,
            $fragments,
            $rootValue,
            $contextValue,
            $variableValues,
            $fieldResolver,
            $operation,
            $errors
        );
    }
}
