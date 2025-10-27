<?php
declare(strict_types=1);

namespace Kickback\Common;

use Kickback\Common\Traits\StaticClassTrait;

/**
* This class shall run all unittests in the \Kickback\Common namespace
* (including all sub-namespaces).
*/
class UnittestEntryPoint
{
    use StaticClassTrait;

    public static function unittests() : void
    {
        // Sort order:
        // * Dependency order as a priority
        // * Alphabetic when packages are peers

        echo("===== Running all ".__NAMESPACE__." unittests =====\n\n");
        \Kickback\Common\Algorithms\Freelist::unittests(); // Dependency-free, for now?
        \Kickback\Common\Meta\Location::unittests();
        \Kickback\Common\Primitives\Meta::unittests();
        \Kickback\Common\Exceptions\ThrowableContextMessageHandling::unittests();
        \Kickback\Common\Exceptions\Reporting\Report::unittests();
        \Kickback\Common\Meta\ZType::unittests(); // Depends on Common\Primitives\Meta.
        \Kickback\Common\Meta\PHP_BinaryOps::unittests(); // Depends on Common\Primitives\Meta.
        \Kickback\Common\Primitives\Arr::unittests(); // May depend on Common\Primitives\Meta in the future?
        \Kickback\Common\Primitives\Mixed_::unittests(); // Depends on Common\Primitives\Meta.
        \Kickback\Common\Primitives\Int_::unittests(); // May depend on Common\Primitives\Meta in the future?
        \Kickback\Common\Primitives\Str::unittests(); // Depends on Common\Primitives\Int_
        \Kickback\Common\Utility\SemVer::unittests(); // Depends on Common\Primitives\Str and Common\Primitives\Int_
        \Kickback\Common\Traits\ClassOfConstantIntegers::unittests(); // Depends on Common\Primitives\Mixed_ and/or Int_.
        echo("----- Finished ".__NAMESPACE__." unittests -----\n\n\n");
    }
}