<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use PhpParser\Node;
use PhpParser\NodeFinder;

class Extension extends OperationExtension
{
    public static array $hooks = [];

    public static function hook(\Closure $cb)
    {
        self::$hooks[] = $cb;
    }

    /**
     * @return Feature[]
     */
    public function features(): array
    {
        return [
            new Feature(
                Feature::AllowedIncludesMethod,
                $this->queryParameterKey('include'),
                ['posts', 'posts.comments', 'books']
            ),
            new Feature(
                Feature::AllowedFiltersMethod,
                $this->queryParameterKey('filter'),
                ['[name]=john', '[email]=gmail']
            ),
            new Feature(
                Feature::AllowedSortsMethod,
                $this->queryParameterKey('sort'),
                ['title', '-title', 'title,-id']
            ),
            new Feature(
                Feature::AllowedFieldsMethod,
                $this->queryParameterKey('fields'),
                ['id', 'title', 'posts.id']
            ),
        ];
    }

    public function runHooks(Operation $operation, Parameter $parameter, Feature $feature): mixed
    {
        foreach (self::$hooks as $hook) {
            $halt = $hook($operation, $parameter, $feature);
            if ($halt) {
                return $halt;
            }
        }

        return false;
    }

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        foreach ($this->features() as $feature) {

            /** @var Node\Expr\MethodCall $methodCall */
            $methodCall = (new NodeFinder())->findFirst(
                $routeInfo->methodNode(),
                $this->methodPredicate($feature->getMethodName()));

            if (! $methodCall) {
                return;
            }

            $parameter = new Parameter($feature->getQueryParameterKey(), 'query');

            $parameter->setSchema(
                Schema::fromType(new StringType())
            );

            if (count($methodCall->args) > 0) {
                if ($this->isArrayParameter($methodCall)) {
                    $values = array_map(function (Node\Expr\ArrayItem $item) {
                        return $item->value->value;
                    }, $methodCall->args[0]->value->items);

                    $feature->setValues($values);
                } elseif ($this->isDynamicParameters($methodCall)) {
                    $values = array_map(function (Node\Arg $arg) {
                        return $arg->value->value;
                    }, $methodCall->args);

                    $feature->setValues($values);
                }
            }

            $parameter->example($feature->getExample());

            $halt = $this->runHooks($operation, $parameter, $feature);

            if (! $halt) {
                $operation->addParameters([$parameter]);
            }
        }
    }

    public function isArrayParameter(Node\Expr\MethodCall $methodCall): bool
    {
        return $methodCall->args[0]->value instanceof Node\Expr\Array_;
    }

    public function isDynamicParameters(Node\Expr\MethodCall $methodCall): bool
    {
        return $methodCall->args[0]->value instanceof Node\Scalar\String_;
    }

    public function methodPredicate($methodName): \Closure
    {
        return function (Node $node) use ($methodName) {
            // todo: check if the methodName is called on QueryBuilder
            return $node instanceof Node\Expr\MethodCall &&
                $node->name instanceof Node\Identifier &&
                $node->name->name === $methodName;
        };
    }

    public function queryParameterKey($featureKey)
    {
        return config("query-builder.parameters.$featureKey");
    }
}
