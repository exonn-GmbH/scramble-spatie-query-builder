<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

class AllowedFiltersExtension extends OperationExtension
{
    use Hookable;

    const MethodName = 'allowedFilters';

    public array $examples = ['[name]=john', '[email]=gmail'];

    public string $configKey = 'query-builder.parameters.filter';

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $helper = new InferHelper;

        $methodCall = Utils::findMethodCall($routeInfo, self::MethodName);

        if (! $methodCall) {
            return;
        }

        $values = $helper->inferValues($methodCall, $routeInfo);

        $parameter = new Parameter(config($this->configKey), 'query');

        $objectType = new ObjectType;
        foreach ($values as $value) {
            $objectType->addProperty($value, new StringType);
        }
        $parameter->setSchema(Schema::fromType($objectType))
            ->example($this->examples);

        $halt = $this->runHooks($operation, $parameter);
        if (! $halt) {
            $operation->addParameters([$parameter]);
        }
    }
}
