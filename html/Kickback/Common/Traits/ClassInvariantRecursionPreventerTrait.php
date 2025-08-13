<?php
declare(strict_types=1);

namespace Kickback\Common\Traits;

trait ClassInvariantRecursionPreventerTrait
{
    private bool $CIRPT__debug_mode = false;
    private bool $CIRPT__processing_invariants = false;

    public final function debug_mode(?bool $new_value) : bool {
        if (!isset($new_value)) {
            return $this->CIRPT__debug_mode;
        }
        $this->CIRPT__debug_mode = $new_value;
        return $this->CIRPT__debug_mode;
    }

    protected final function debug_invariant_check(string $label) : bool
    {
        if (!$this->CIRPT__debug_mode) {
            return true;
        } else {
            $this->CIRPT__try_assert_invariants($label);

            // If we didn't throw, then the invariant held.
            return true;
        }
    }

    private function CIRPT__try_assert_invariants(string $label) : void
    {
        $processing_invariants = &$this->CIRPT__processing_invariants;

        // Avoid stack recursion when the functions called in the
        // invariant cause the invariants to be invoked which
        // causes those functions to be called which causes
        // the invariants to be invoked which... you know how it goes.
        if ( $processing_invariants ) {
            return;
        }

        try {
            $processing_invariants = true;
            $this->assert_invariants($label);
        } finally {
            // Make REALLY sure we turn this off, because it could
            // kill all invariant checks and invalidate testing.
            // (Hence the `finally` clause to make sure it always
            // gets executed, even if something throws.)
            $processing_invariants = false;
        }

    }
}
?>
