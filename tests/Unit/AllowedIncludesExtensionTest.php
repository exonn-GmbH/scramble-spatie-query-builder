<?php

use Exonn\ScrambleSpatieQueryBuilder\AllowedIncludesExtension;
use Illuminate\Support\Facades\Route;

test('test AllowedIncludesExtensions', function () {

    $queryParam = 'include';

    config()->set('query-builder.parameters.include', $queryParam);

    $result = generateForRoute(function () {
        return Route::get('test', [
            AllowedIncludesExtensionController::class, 'a',
        ]);
    }, [
        AllowedIncludesExtension::class,
    ]);

    expect($result['paths']['/test']['get']['parameters'][0])->toBe([
        'name' => $queryParam,
        'in' => 'query',
        'schema' => [
            'anyOf' => [
                [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => [
                            'foo',
                            'bar',
                        ],
                    ],
                ],
                [
                    'type' => 'string',
                ],
            ],
        ],
        'example' => ['posts', 'posts.comments', 'books'],
    ]);

});

class AllowedIncludesExtensionController extends \Illuminate\Routing\Controller
{
    public function a(): Illuminate\Http\Resources\Json\JsonResource
    {
        \Spatie\QueryBuilder\QueryBuilder::for(null)
            ->allowedIncludes(['foo', 'bar']);

        return $this->unknown_fn();
    }
}
