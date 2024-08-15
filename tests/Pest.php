<?php

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\OperationBuilder;
use Dedoc\Scramble\Support\OperationExtensions\DeprecationExtension;
use Dedoc\Scramble\Support\OperationExtensions\ErrorResponsesExtension;
use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;
use Dedoc\Scramble\Support\OperationExtensions\RequestEssentialsExtension;
use Dedoc\Scramble\Support\OperationExtensions\ResponseExtension;
use Exonn\ScrambleSpatieQueryBuilder\Tests\TestCase;
use Illuminate\Routing\Route;

uses(TestCase::class)->in(__DIR__);

function generateForRoute(Closure $param, array $extensions = [])
{
    $route = $param();

    app()->when(OperationBuilder::class)
        ->needs('$extensionsClasses')
        ->give(function () use ($extensions) {
            return array_merge([
                RequestEssentialsExtension::class,
                RequestBodyExtension::class,
                ErrorResponsesExtension::class,
                ResponseExtension::class,
                DeprecationExtension::class,
            ], $extensions);
        });

    Scramble::routes(fn (Route $r) => $r->uri === $route->uri);

    return app()->make(\Dedoc\Scramble\Generator::class)();
}
