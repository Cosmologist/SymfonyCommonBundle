<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\ExpressionLanguage;

use Cosmologist\Gears\NumberType;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

class AstHelper
{
    /**
     * @param Node $node
     *
     * @return string
     */
    public static function getName(Node $node): string
    {
        return $node->attributes['name'];
    }

    /**
     * @param Node $node
     *
     * @return array|mixed|null
     */
    public static function getValue(Node $node)
    {
        if (self::isArrayNode($node)) {
            return self::getArrayValue($node);
        }
        if (self::isConstantNode($node)) {
            return self::getConstantValue($node);
        }

        return null;
    }

    /**
     * @param ArrayNode $node
     *
     * @return array
     */
    public static function getArrayValue(ArrayNode $arrayNode): array
    {
        $keys = $values = [];
        foreach ($arrayNode->nodes as $i => $itemNode) {
            array_push(NumberType::odd($i) ? $values : $keys, self::getValue($itemNode));
        }

        return array_combine($keys, $values);
    }

    /**
     * @param ConstantNode $node
     *
     * @return mixed
     */
    public static function getConstantValue(ConstantNode $constantNode)
    {
        return $constantNode->attributes['value'];
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    public static function getLeft(Node $node): Node
    {
        return $node->nodes['left'];
    }

    /**
     * @param Node $node
     *
     * @return string
     */
    public static function getOperator(Node $node): string
    {
        return $node->attributes['operator'];
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    public static function getRight(Node $node): Node
    {
        return $node->nodes['left'];
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    public static function isArrayNode(Node $node): bool
    {
        return $node instanceof ArrayNode;
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    public static function isBinaryNode(Node $node): bool
    {
        return $node instanceof BinaryNode;
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    public static function isConstantNode(Node $node): bool
    {
        return $node instanceof ConstantNode;
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    public static function isNameNode(Node $node): bool
    {
        return $node instanceof NameNode;
    }
}