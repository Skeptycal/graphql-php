<?php

namespace Digia\GraphQL\Language\AST\Node;

use Digia\GraphQL\Language\AST\KindEnum;
use Digia\GraphQL\Language\AST\Node\Behavior\TypeTrait;
use Digia\GraphQL\Language\AST\Node\Contract\DefinitionNodeInterface;

class OperationTypeDefinitionNode extends AbstractNode implements DefinitionNodeInterface
{

    use TypeTrait;

    /**
     * @var string
     */
    protected $kind = KindEnum::OPERATION_TYPE_DEFINITION;

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