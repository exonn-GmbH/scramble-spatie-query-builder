<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\RouteInfo;

class AllowedFilterModesExtension extends OperationExtension {
    use Hookable;
    const MethodName = 'allowedFilters';
    public array $examples = ['[name]=starts_with', '[email]=exact'];
    public string $configKey = 'query-builder.parameters.filter_mode';

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $helper = new InferHelper();

        $methodCall = Utils::findMethodCall($routeInfo,self::MethodName);

        if(!$methodCall) {
            return;
        }

        $values = $helper->inferValues($methodCall, $routeInfo);

        $parameter = new Parameter(config($this->configKey), 'query');

        $objectType = new ObjectType();

        $filterMode = new ArrayType();
        $filterMode->items->enum([
            'starts_with',
            'ends_with',
            'exact',
            'partial'
        ]);

        foreach($values as $value) {
            $objectType->addProperty($value, $filterMode);
        }
        $parameter->setSchema(Schema::fromType($objectType))
            ->example($this->examples);

        $halt = $this->runHooks($operation, $parameter);
        if (! $halt) {
            $operation->addParameters([$parameter]);
        }
    }
}