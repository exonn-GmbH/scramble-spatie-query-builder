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

    /**
     * @return Feature[]
     */
    public function features(): array
    {
        return [
            new Feature(
                Feature::AllowedIncludesMethod,
                config('query-builder.parameters.include'),
                ['posts', 'posts.comments', 'books']
            ),
            new Feature(
                Feature::AllowedFiltersMethod,
                config('query-builder.parameters.filter'),
                ['[name]=john', '[email]=gmail']
            ),
            new Feature(
                Feature::AllowedSortsMethod,
                config('query-builder.parameters.sort'),
                ['title', '-title', 'title,-id']
            ),
            new Feature(
                Feature::AllowedFieldsMethod,
                config('query-builder.parameters.fields'),
                ['id', 'title', 'posts.id']
            ),
        ];
    }

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        foreach ($this->features() as $feature) {

            /** @var Node\Expr\MethodCall $methodCall */
            $methodCall = (new NodeFinder())->findFirst(
                $routeInfo->methodNode(),
                fn (Node $node) =>
                    // todo: check if the methodName is called on QueryBuilder
                    $node instanceof Node\Expr\MethodCall &&
                        $node->name instanceof Node\Identifier &&
                        $node->name->name === $feature->getMethodName()
            );

            if (! $methodCall) {
                return;
            }

            $parameter = new Parameter($feature->getQueryParameterKey(), 'query');

            $parameter->setSchema(
                Schema::fromType(new StringType())
            );

            $feature->setValues($this->getValues($methodCall));

            $parameter->example($feature->getExample());

            $halt = $this->runHooks($operation, $parameter, $feature);

            if (! $halt) {
                $operation->addParameters([$parameter]);
            }
        }
    }

    public static function hook(\Closure $cb)
    {
        self::$hooks[] = $cb;
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

    public function getValues(Node\Expr\MethodCall $methodCall): array
    {
        // ->allowedIncludes()
        if (count($methodCall->args) === 0) {
            return [];
        }

        // ->allowedIncludes(['posts', 'posts.author'])
        if ($methodCall->args[0]->value instanceof Node\Expr\Array_) {
            return array_map(function (Node\Expr\ArrayItem $item) {
                return $item->value->value;
            }, $methodCall->args[0]->value->items);
        }

        // ->allowedIncludes('posts', 'posts.author')
        if ($methodCall->args[0]->value instanceof Node\Scalar\String_) {
            return array_map(function (Node\Arg $arg) {
                return $arg->value->value;
            }, $methodCall->args);
        }

        return [];
    }
}
