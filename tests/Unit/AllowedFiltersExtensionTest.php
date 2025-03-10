<?php

use Exonn\ScrambleSpatieQueryBuilder\AllowedFiltersExtension;
use Illuminate\Support\Facades\Route;

test('test AllowedFiltersExtensions', function () {

    $queryParam = 'filter';

    config()->set('query-builder.parameters.filter', $queryParam);

    $result = generateForRoute(
        fn() => Route::get('test', [AllowedFiltersExtensionController::class, 'a']),
        [AllowedFiltersExtension::class]
    );

    expect($result['paths']['/test']['get']['parameters'][0])->toBe([
        'name' => $queryParam,
        'in' => 'query',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'foo' => [
                    'type' => 'string',
                ],
                'bar' => [
                    'type' => 'string',
                ],
            ],
        ],
        'example' => ['[name]=john', '[email]=gmail'],
    ]);

});

class AllowedFiltersExtensionController extends \Illuminate\Routing\Controller
{
    public function a(): Illuminate\Http\Resources\Json\JsonResource
    {
        \Spatie\QueryBuilder\QueryBuilder::for(null)
            ->allowedFilters(['foo', 'bar']);

        return $this->unknown_fn();
    }
}
