<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

use Kickback\Common\Traits\StaticClassTrait;
use Kickback\Common\Traits\ClassOfConstantIntegersTrait;

// TODO: This class should probably be moved else, but I'm not sure where.
final class CategoriesOfCallables
{
    use StaticClassTrait;
    use ClassOfConstantIntegersTrait;

    public const STATIC_AS_STRING   = 1;
    public const STATIC_AS_ARRAY    = 2;
    public const FUNCTION_AS_STRING = 3;
    public const METHOD_AS_ARRAY    = 4;
    public const CLOSURE_INSTANCE   = 5;
    public const INVOKABLE_OBJECT   = 6;
    public const UNKNOWN            = 7;

    /**
    * @param   callable  $callable
    * @return  (
    *     $callable is string   ? self::STATIC_AS_STRING|self::FUNCTION_AS_STRING :
    *     $callable is array    ? self::STATIC_AS_ARRAY |self::METHOD_AS_ARRAY    :
    *     $callable is \Closure ? self::CLOSURE_INSTANCE :
    *     $callable is object   ? self::INVOKABLE_OBJECT :
    *     self::UNKNOWN)
    *
    * \@phpstan-assert-if-true  string  self::* is self::STATIC_AS_STRING
    */
    public static function from_callable(callable $callable) : int
    {
        // Thanks goes to StackOverflow poster `Bigdot` for enumerating the
        // possible `callable` types and how one might stringize them:
        // https://stackoverflow.com/a/68113840
        switch (true)
        {
            case is_string($callable) && (strpos($callable, '::') !== false):
                return self::STATIC_AS_STRING;
            case is_string($callable):
                return self::FUNCTION_AS_STRING;
            case is_array($callable) && is_object($callable[0]):
                return self::METHOD_AS_ARRAY;
            case is_array($callable):
                return self::STATIC_AS_ARRAY;
            case $callable instanceof \Closure:
                return self::CLOSURE_INSTANCE;
            case is_object($callable):
                return self::INVOKABLE_OBJECT;
            default:
                return self::UNKNOWN;
        }
    }
}

?>
