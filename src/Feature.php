<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

class Feature
{
    const AllowedIncludesMethod = 'allowedIncludes';

    const AllowedFiltersMethod = 'allowedFilters';

    const AllowedSortsMethod = 'allowedSorts';

    const AllowedFieldsMethod = 'allowedFields';

    public function __construct(
        protected string $methodName,
        protected string $queryParameterKey,
        protected array $example = [],
        protected array $values = []
    ) {

    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getQueryParameterKey(): string
    {
        return $this->queryParameterKey;
    }

    public function getExample(): array
    {
        return $this->example;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values)
    {
        $this->values = $values;
    }

    public function isForIncludes(): bool {
        return $this->methodName === self::AllowedIncludesMethod;
    }
    public function isForFields(): bool {
        return $this->methodName === self::AllowedFieldsMethod;
    }
    public function isForFilters(): bool {
        return $this->methodName === self::AllowedFiltersMethod;
    }
    public function isForSorts(): bool {
        return $this->methodName === self::AllowedSortsMethod;
    }
}
