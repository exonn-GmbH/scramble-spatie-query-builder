<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter as SpatieAllowedFilter;

class AllowedFilter extends SpatieAllowedFilter
{
    const FilterModesQueryParamConfigKey = 'query-builder.parameters.filter_mode';

    public static function autoDetect(Request $request, string $key, FilterModeEnum $default_mode = FilterModeEnum::Contains)
    {
        $mode = $request->input(config(self::FilterModesQueryParamConfigKey).'.'.$key) ?? $default_mode->value;

        return match ($mode) {
            FilterModeEnum::StartsWith->value => AllowedFilter::beginsWithStrict($key),
            FilterModeEnum::EndsWith->value => AllowedFilter::endsWithStrict($key),
            FilterModeEnum::Exact->value => AllowedFilter::exact($key),
            default => AllowedFilter::partial($key),
        };
    }
}
