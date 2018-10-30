<?php

namespace Digia\GraphQL\Execution\Strategy;

use Digia\GraphQL\Execution\UndefinedFieldException;
use Digia\GraphQL\Type\Definition\ObjectType;
use React\Promise\ExtendedPromiseInterface;
use function React\Promise\all as promiseAll;

/**
 * Implements the "Evaluating selection sets" section of the spec
 * for "read" mode.
 *
 * Class ReadStrategy
 * @package Digia\GraphQL\Execution\Strategy
 */
class ReadStrategy extends AbstractExecutionStrategy
{
    /**
     * @param ObjectType $objectType
     * @param mixed      $rootValue
     * @param array      $path
     * @param array      $fields
     * @return array
     * @throws \Throwable
     */
    public function executeFields(
        ObjectType $objectType,
        $rootValue,
        array $path,
        array $fields
    ): array {
        $results            = [];
        $doesContainPromise = false;

        foreach ($fields as $fieldName => $fieldNodes) {
            $fieldPath   = $path;
            $fieldPath[] = $fieldName;

            try {
                $result = $this->resolveField($objectType, $rootValue, $fieldNodes, $fieldPath);
            } catch (UndefinedFieldException $exception) {
                continue;
            }

            $results[$fieldName] = $result;
            $doesContainPromise  = !$doesContainPromise && $result instanceof ExtendedPromiseInterface;
        }

        if (!$doesContainPromise) {
            return $results;
        }

        // Otherwise, results is a map from field name to the result of resolving that
        // field, which is possibly a promise. Return a promise that will return this
        // same map, but with any promises replaced with the values they resolved to.
        $keys = \array_keys($results);

        promiseAll(\array_values($results))
            ->then(function ($values) use ($keys, &$results) {
                foreach ($values as $i => $value) {
                    $results[$keys[$i]] = $value;
                }
            });

        return $results;
    }
}
