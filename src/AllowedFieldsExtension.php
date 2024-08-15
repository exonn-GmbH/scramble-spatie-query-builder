<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

class AllowedFieldsExtension extends OperationExtension
{
    use Hookable;

    const MethodName = 'allowedFields';

    public array $examples = ['id', 'title', 'posts.id'];

    public string $configKey = 'query-builder.parameters.fields';

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $helper = new InferHelper;
        $methodCall = Utils::findMethodCall($routeInfo, self::MethodName);

        if (! $methodCall) {
            return;
        }

        $parameter = new Parameter(config($this->configKey), 'query');

        $values = $helper->inferValues($methodCall, $routeInfo);
        $arrayType = new ArrayType;
        $arrayType->items->enum($values);

        $parameter->setSchema(Schema::fromType((new AnyOf)->setItems([
            $arrayType,
            new StringType,
        ])))->example($this->examples);

        $halt = $this->runHooks($operation, $parameter);
        if (! $halt) {
            $operation->addParameters([$parameter]);
        }
    }
}
