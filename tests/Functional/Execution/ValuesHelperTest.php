<?php

namespace Digia\GraphQL\Test\Functional\Execution;

use Digia\GraphQL\Execution\ExecutionContext;
use Digia\GraphQL\Execution\ValuesHelper;
use Digia\GraphQL\Language\Node\ArgumentsAwareInterface;
use Digia\GraphQL\Language\Node\OperationDefinitionNode;
use Digia\GraphQL\Language\Node\StringValueNode;
use Digia\GraphQL\Test\TestCase;
use function Digia\GraphQL\Execution\coerceArgumentValues;
use function Digia\GraphQL\Execution\coerceVariableValues;
use function Digia\GraphQL\parse;
use function Digia\GraphQL\Type\booleanType;
use function Digia\GraphQL\Type\newInputObjectType;
use function Digia\GraphQL\Type\newNonNull;
use function Digia\GraphQL\Type\newObjectType;
use function Digia\GraphQL\Type\newSchema;
use function Digia\GraphQL\Type\stringType;

class ValuesHelperTest extends TestCase
{
    public function testCoerceArgumentValues()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $schema = newSchema([
            'query' => newObjectType([
                'name'   => 'Greeting',
                'fields' => [
                    'greeting' => [
                        'type' => stringType(),
                        'args' => [
                            'name' => [
                                'type' => stringType(),
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $documentNode = parse('query Hello($name: String) { Greeting(name: $name) }');
        /** @var OperationDefinitionNode $operation */
        $operation = $documentNode->getDefinitions()[0];
        /** @var ArgumentsAwareInterface $node */
        $node = $operation->getSelectionSet()->getSelections()[0];
        /** @noinspection PhpUnhandledExceptionInspection */
        $definition = $schema->getQueryType()->getFields()['greeting'];

        $context = new ExecutionContext(
            $schema, [], null, null, ['name' => 'Han Solo'], null, $operation, []
        );

        $args = coerceArgumentValues($definition, $node, $context->getVariableValues());

        $this->assertSame(['name' => 'Han Solo'], $args);
    }

    public function testCoerceVariableValues(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $schema = newSchema([
            'query' => newObjectType([
                'name'   => 'nonNullBoolean',
                'fields' => [
                    'greeting' => [
                        'type' => stringType(),
                        'args' => [
                            'shout' => [
                                'type' => newNonNull(booleanType()),
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $documentNode = parse('
            query ($shout: Boolean!) {
                nonNullBoolean(shout: $shout)
            }
         ');

        /** @var OperationDefinitionNode $operation */
        $operation           = $documentNode->getDefinitions()[0];
        $variableDefinitions = $operation->getVariableDefinitions();

        // Try with true and false and null (null should give errors, the rest shouldn't)
        $coercedValue = coerceVariableValues($schema, $variableDefinitions, ['shout' => true]);
        $this->assertSame(['shout' => true], $coercedValue->getValue());
        $this->assertFalse($coercedValue->hasErrors());

        $coercedValue = coerceVariableValues($schema, $variableDefinitions, ['shout' => false]);
        $this->assertSame(['shout' => false], $coercedValue->getValue());
        $this->assertFalse($coercedValue->hasErrors());

        $coercedValue = coerceVariableValues($schema, $variableDefinitions, ['shout' => null]);
        $this->assertEquals([], $coercedValue->getValue());
        $this->assertTrue($coercedValue->hasErrors());
    }

    public function testCoerceValuesForInputObjectTypes(): void
    {
        // Test input object types
        /** @noinspection PhpUnhandledExceptionInspection */
        $schema = newSchema([
            'query' => newObjectType([
                'name'   => 'Query',
                'fields' => [
                    'inputObjectField' => [
                        'type' => booleanType(),
                        'args' => [
                            'inputObject' => [
                                'type' => newInputObjectType([
                                        'name'   => 'InputObject',
                                        'fields' => [
                                            'a' => ['type' => stringType()],
                                            'b' => ['type' => newNonNull(stringType())]
                                        ]
                                    ]
                                )
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $documentNode = parse('
            query ($inputObject: InputObject!) {
                inputObjectField(inputObject: $inputObject)
            }
         ');

        /** @var OperationDefinitionNode $operation */
        $operation           = $documentNode->getDefinitions()[0];
        $variableDefinitions = $operation->getVariableDefinitions();

        // Test with a missing non-null string
        $coercedValue = coerceVariableValues($schema, $variableDefinitions, [
            'inputObject' => [
                'a' => 'some string'
            ]
        ]);

        $this->assertTrue($coercedValue->hasErrors());
        $this->assertEquals('Variable "$inputObject" got invalid value {"a":"some string"}; Field value.b of required type String! was not provided.',
            $coercedValue->getErrors()[0]->getMessage());

        // Test again with all variables, no errors expected
        $coercedValue = coerceVariableValues($schema, $variableDefinitions, [
            'inputObject' => [
                'a' => 'some string',
                'b' => 'some other required string',
            ]
        ]);

        $this->assertFalse($coercedValue->hasErrors());

        // Test with non-nullable boolean input fields
        /** @noinspection PhpUnhandledExceptionInspection */
        $schema = newSchema([
            'query' => newObjectType([
                'name'   => 'Query',
                'fields' => [
                    'inputObjectField' => [
                        'type' => booleanType(),
                        'args' => [
                            'inputObject' => [
                                'type' => newInputObjectType([
                                        'name'   => 'InputObject',
                                        'fields' => [
                                            'a' => ['type' => booleanType()],
                                            'b' => ['type' => newNonNull(booleanType())]
                                        ]
                                    ]
                                )
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $documentNode = parse('
            query ($inputObject: InputObject!) {
                inputObjectField(inputObject: $inputObject)
            }
         ');

        /** @var OperationDefinitionNode $operation */
        $operation           = $documentNode->getDefinitions()[0];
        $variableDefinitions = $operation->getVariableDefinitions();

        // Test with a missing non-null string
        $coercedValue = coerceVariableValues($schema, $variableDefinitions, [
            'inputObject' => [
                'a' => true
            ]
        ]);

        $this->assertTrue($coercedValue->hasErrors());
        $this->assertEquals('Variable "$inputObject" got invalid value {"a":true}; Field value.b of required type Boolean! was not provided.',
            $coercedValue->getErrors()[0]->getMessage());

        // Test again with all fields present, all booleans true
        $coercedValue = coerceVariableValues($schema, $variableDefinitions, [
            'inputObject' => [
                'a' => true,
                'b' => true,
            ]
        ]);

        $this->assertFalse($coercedValue->hasErrors());

        // Test again with all fields present, all booleans false (this has been problematic before)
        $coercedValue = coerceVariableValues($schema, $variableDefinitions, [
            'inputObject' => [
                'a' => false,
                'b' => false,
            ]
        ]);

        $this->assertFalse($coercedValue->hasErrors());
    }
}
