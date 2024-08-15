<?php

namespace Exonn\ScrambleSpatieQueryBuilder;

use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;

trait Hookable
{
    public static array $hooks = [];

    public static function hook(\Closure $cb)
    {
        self::$hooks[] = $cb;
    }

    public function runHooks(Operation $operation, Parameter $parameter): mixed
    {
        foreach (self::$hooks as $hook) {
            $halt = $hook($operation, $parameter);
            if ($halt) {
                return $halt;
            }
        }

        return false;
    }
}
