# Scramble extension for Spatie Query Builder
![Preview](./.github/preview.png)

## Introduction
This is the Scramble extension, which detects the usage of the Spatie query builder in your api routes and automatically adds applicable query parameters to the openapi definition.

## Installation

```
composer install exonn-gmbh/scramble-spatie-query-builder
```

## Usage
1. Register the extension in your `config/scramble.php` file
```php
'extensions' => [
    // ...
    \Exonn\ScrambleSpatieQueryBuilder\AllowedFieldsExtension::class,
    \Exonn\ScrambleSpatieQueryBuilder\AllowedSortsExtension::class,
    \Exonn\ScrambleSpatieQueryBuilder\AllowedFiltersExtension::class,
    \Exonn\ScrambleSpatieQueryBuilder\AllowedIncludesExtension::class,
//    \Exonn\ScrambleSpatieQueryBuilder\AllowedFilterModesExtension::class
],
```
2. You are done, now check your Scramble docs for routes that use Spatie query builder, you should see new query parameters documented

## Customization
By default this extension automatically updates openapi definition for you, but if you want to customize its default behaviour, you can do it in the following way

1. Open your ```AppServiceProvider.php``` and add the following code example in the ```boot``` method

```php
public function boot(): void
{
    // ...
    AllowedIncludesExtension::hook(function(Operation $operation, Parameter $parameter) {
        // Customize the example
        $parameter->example(['repositories.issues', 'repositories']);
        // Customize the description
        $parameter->description('Allows you to include additional model relations in the response');
    });
}
```
2. Customize for your needs

