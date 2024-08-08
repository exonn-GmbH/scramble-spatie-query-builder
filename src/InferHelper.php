<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Infer\Services\FileParser;
use Dedoc\Scramble\Support\RouteInfo;
use PhpParser\Node;
use PhpParser\NodeFinder;

class InferHelper {
    const NOT_SUPPORTED_KEY = '--not_supported--';

    public function inferValues(Node\Expr\MethodCall $methodCall, RouteInfo $routeInfo): array
    {
        // ->allowedIncludes()
        if (count($methodCall->args) === 0) {
            return [];
        }

        if ($methodCall->args[0]->value instanceof Node\Expr\Array_) {
            return array_map(function (Node\Expr\ArrayItem $item) {

                if ($item->value instanceof Node\Scalar\String_) {
                    return $item->value->value;
                }
                if ($item->value instanceof Node\Expr\StaticCall) {
                    // Check if the static call is AllowedSort::custom
                    if ($item->value->class instanceof Node\Name
                        && $item->value->class->toString() === 'Spatie\QueryBuilder\AllowedSort'
                        && $item->value->name instanceof Node\Identifier
                        && $item->value->name->name === 'custom') {
                        $customSortName = $item->value->args[0]->value->value;
                        return $customSortName;
                    }
                    return $this->inferValueFromStaticCall($item->value);
                }
                return self::NOT_SUPPORTED_KEY;
            }, $methodCall->args[0]->value->items);
        }

        // ->allowedIncludes('posts', 'posts.author')
        if ($methodCall->args[0]->value instanceof Node\Scalar\String_) {
            return array_map(fn(Node\Arg $arg) => $arg->value->value, $methodCall->args);
        }

        // ->allowedIncludes($this->includes)
        if($methodCall->args[0]->value instanceof Node\Expr\PropertyFetch) {
            return $this->inferValuesFromPropertyFetch($methodCall->args[0]->value, $routeInfo);
        }

        // ->allowedIncludes($this->includes())
        if($methodCall->args[0]->value instanceof Node\Expr\MethodCall) {
            return $this->inferValuesFromMethodCall($methodCall->args[0]->value, $routeInfo);
        }

        return [];
    }

    public function inferValuesFromMethodCall(Node\Expr\MethodCall $node, RouteInfo $routeInfo) {
        if($node->var->name !== "this") {
            return [];
        }

        $statements = FileParser::getInstance()
            ->parseContent($this->getControllerClassContent($routeInfo))
            ->getStatements();

        /** @var Node\Stmt\ClassMethod $node */
        $node = (new NodeFinder())
            ->findFirst(
                $statements,
                fn (Node $visitedNode) =>
                    $visitedNode instanceof Node\Stmt\ClassMethod && $visitedNode->name->name === $node->name->name
            );

        /** @var Node\Stmt\Return_|null $return */
        $return = (new NodeFinder())
            ->findFirst($node->stmts, fn(Node $node) => $node instanceof Node\Stmt\Return_);

        if(!$return) {
            return [];
        }

        if(!$return->expr instanceof Node\Expr\Array_) {
            return [];
        }

        return array_map(
            function(Node\ArrayItem $item){
                if($item->value instanceof Node\Scalar\String_) {
                    return $item->value->value;
                }
                // AllowedFilter::callback(...), AllowedSort::callback
                if($item->value instanceof Node\Expr\StaticCall)  {
                    return $this->inferValueFromStaticCall($item->value);
                }

                return self::NOT_SUPPORTED_KEY;
            },
            $return->expr->items
        );
    }


    public function getControllerClassContent(RouteInfo $routeInfo) {
        [$class] = explode('@', $routeInfo->route->getAction('uses'));
        $reflection = new \ReflectionClass($class);
        return file_get_contents($reflection->getFileName());
    }

    public function inferValuesFromPropertyFetch(Node\Expr\PropertyFetch $node, RouteInfo $routeInfo) {

        if($node->var->name !== "this") {
            return [];
        }

        $statements = FileParser::getInstance()
            ->parseContent($this->getControllerClassContent($routeInfo))
            ->getStatements();

        /** @var Node\Stmt\Property $node */
        $node = (new NodeFinder())
            ->findFirst(
                $statements,
                fn (Node $visitedNode) =>
                    $visitedNode instanceof Node\Stmt\Property && $visitedNode->props[0]->name->name === $node->name->name
            );

        if(!$node->props[0]->default instanceof Node\Expr\Array_) {
            return [];
        }

        return array_map(
            fn(Node\ArrayItem $item) => $item->value->value,
            $node->props[0]->default->items
        );
    }

    public function inferValueFromStaticCall(Node\Expr\StaticCall $node) {
        switch ($node->class->name) {
            case "AllowedFilter":
                return $this->inferValueFromAllowedFilter($node);
            case "AllowedSort":
                return $this->inferValueFromAllowedSort($node);
            default:
                return self::NOT_SUPPORTED_KEY;
        }
    }

    public function inferValueFromAllowedFilter(Node\Expr\StaticCall $node) {
        switch ($node->name->name){
            case "autoDetect":
                if($node->args[1]->value instanceof Node\Scalar\String_) {
                    return $node->args[1]->value->value;
                }
                return self::NOT_SUPPORTED_KEY;
            case "callback":
            case "partial":
            case "custom":
            case "exact":
            case "beginsWithStrict":
            case "endsWithStrict":
                if($node->args[0]->value instanceof Node\Scalar\String_) {
                    return $node->args[0]->value->value;
                }
            default:
                return self::NOT_SUPPORTED_KEY;
        }
    }

    public function inferValueFromAllowedSort(Node\Expr\StaticCall $node) {
        switch ($node->name->name){
            case "callback":
            case "field":
                if($node->args[0]->value instanceof Node\Scalar\String_) {
                    return $node->args[0]->value->value;
                }
            default:
                return self::NOT_SUPPORTED_KEY;
        }
    }



}