<?php

namespace Digia\GraphQL\Language\AST\Node;

use Digia\GraphQL\Language\AST\Node\Behavior\DirectivesTrait;
use Digia\GraphQL\Language\AST\Node\Behavior\NameTrait;
use Digia\GraphQL\Language\AST\Node\Behavior\SelectionSetTrait;
use Digia\GraphQL\Language\AST\Node\Behavior\VariableDefinitionsTrait;
use Digia\GraphQL\Language\AST\Node\Contract\ExecutableDefinitionNodeInterface;

class OperationDefinitionNode extends AbstractNode implements ExecutableDefinitionNodeInterface
{

    use NameTrait;
    use DirectivesTrait;
    use VariableDefinitionsTrait;
    use SelectionSetTrait;

    /**
     * @var string
     */
    protected $operation;

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
}