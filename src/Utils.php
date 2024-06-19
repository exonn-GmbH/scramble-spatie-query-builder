<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Support\RouteInfo;
use PhpParser\Node;
use PhpParser\NodeFinder;

class Utils {
    public static function findMethodCall(RouteInfo $routeInfo, string $methodName): ?Node\Expr\MethodCall {
        /** @var Node\Expr\MethodCall|null $methodCall */
        $methodCall = (new NodeFinder())->findFirst(
            $routeInfo->methodNode(),
            fn (Node $node) =>
                // todo: check if the methodName is called on QueryBuilder
                $node instanceof Node\Expr\MethodCall &&
                $node->name instanceof Node\Identifier &&
                $node->name->name === $methodName
        );

        return $methodCall;
    }
}