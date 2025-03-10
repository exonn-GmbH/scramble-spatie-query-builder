<?php

use Exonn\ScrambleSpatieQueryBuilder\AllowedFilter;
use Exonn\ScrambleSpatieQueryBuilder\AllowedFilterModesExtension;
use Illuminate\Support\Facades\Route;

test('test AllowedFilterModesExtensions', function () {

    $queryParam = 'filter_mode';

    config()->set(AllowedFilter::FilterModesQueryParamConfigKey, $queryParam);

    $result = generateForRoute(
        fn() => Route::get('test', [AllowedFilterModesExtensionController::class, 'a']),
        [AllowedFilterModesExtension::class]
    );

    expect($result['paths']['/test']['get']['parameters'][0])->toBe([
        'name' => $queryParam,
        'in' => 'query',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'foo' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => [
                            'starts_with',
                            'ends_with',
                            'exact',
                            'partial',
                        ],
                    ],
                ],
                'bar' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => [
                            'starts_with',
                            'ends_with',
                            'exact',
                            'partial',
                        ],
                    ],
                ],
            ],

        ],
        'example' => ['[name]=starts_with', '[email]=exact'],
    ]);

});

class AllowedFilterModesExtensionController extends \Illuminate\Routing\Controller
{
    public function a(): Illuminate\Http\Resources\Json\JsonResource
    {
        \Spatie\QueryBuilder\QueryBuilder::for(null)
            ->allowedFilters(['foo', 'bar']);

        return $this->unknown_fn();
    }
}
