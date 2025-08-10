<?php
declare(strict_types=1);

namespace Kickback\Common\Algorithms;

/**
* @template T of object
*/
class Freelist
{
    /**
    * @var array<T>
    */
    private array $slots = [];
    private int   $num_slots_allocated = 0;

    /** @return T[] */
    public function all_allocated_objects() : array
    {
        return array_slice($this->slots, 0, $this->num_slots_allocated);
    }

    /**
    * @param  class-string<T>|callable():T  $constructor
    * @return T
    */
    public function allocate(string|callable $constructor) : object
    {
        $this->debug_invariant_check('before `allocate(...)`');
        $capacity = count($this->slots);
        $nalloc = $this->num_slots_allocated;
        $nfree = $capacity - $nalloc;
        if ( 0 === $nfree ) {
            if ( !is_callable($constructor) ) {
                $class_fqn = $constructor;
                $this->slots[] = new $class_fqn();
            } else {
                $this->slots[] = $constructor();
            }
        }

        $allocated = $this->allocate_lowest_indexed_free_element();
        $this->debug_invariant_check('after `allocate(...)`');
        return $allocated;
    }

    /**
    * @return T
    */
    private function allocate_lowest_indexed_free_element() : object
    {
        $capacity = count($this->slots);
        $nalloc = $this->num_slots_allocated;
        $nfree = $capacity - $nalloc;
        assert(0 < $nfree);

        $allocated = $this->slots[$nalloc];
        $this->assign_element_index($allocated, $nalloc);

        $this->num_slots_allocated++;

        if ( $allocated instanceof Freelist__Indexable ) {
            $allocated->on_allocate();
        }
        return $allocated;
    }

    /**
    * Attempt to recycle an already-allocated element.
    *
    * If the `$to_recycle` argument is free, then `$to_recycle` will be
    * replaced with the result of calling `allocate`, and this method
    * will return `false`. The outgoing object may or may not be
    * the same object as the incoming object.
    *
    * If the `$to_recycle` argument is not free (that is, it is allocated),
    * then the `$to_recycle` parameter will be unmodified, and this method
    * will return `true`.
    *
    * For anyone wondering why `allocate` requires a constructor or class-name
    * argument, but this function does not:
    * If the value passed into `recycle` is free, then the list is
    * guaranteed to have _at least_ one free element. Thus, this method
    * can make assumptions that `allocate` can't, and will skip the
    * "do we need to make a new element?" portion of the allocator.
    *
    * @param      T     $to_recycle
    * @return     bool  `true` if the original `$to_recycle` object was
    *     allocated and can be used as-is. `false` if `$to_recycle` was
    *     unallocated and now points to a (possibly different) newly
    *     allocated object.
    */
    public function recycle(object &$to_recycle) : bool
    {
        $this->debug_invariant_check('before `recycle(\$to_recycle)`');
        if ( $this->is_free($to_recycle) ) {
            $to_recycle = $this->allocate_lowest_indexed_free_element();
            $result = false;
        } else {
            $result = true;
        }
        $this->debug_invariant_check('after `recycle(\$to_recycle)`');
        return $result;
    }

    /**
    * @param  T  $to_free
    */
    public function free(object $to_free) : void
    {
        $this->debug_invariant_check('before `free(\$to_free)`');
        $capacity = count($this->slots);
        $nalloc_before = $this->num_slots_allocated;
        assert($nalloc_before <= $capacity);
        assert($nalloc_before > 0);

        $free_at = $this->find_element_index($to_free);
        assert($free_at >= 0);
        assert($free_at < $nalloc_before);

        if ( $to_free instanceof Freelist__Indexable ) {
            $to_free->on_free();
        }

        if ( $free_at === $nalloc_before ) {
            // Fast case: free'ing the last allocated element.
            // In this case, we don't need to do any swapping/shuffling,
            // we just decrement the allocation count and get out early.
            $this->num_slots_allocated--;
        } else {
            // Slower case (but still probably pretty fast) :
            // Swap the element-to-be-free'd with the last element,
            // then decrement the allocation count to mark it as free.
            $this->free_by_swapping($free_at, $to_free);
        }

        $this->debug_invariant_check("after `free(\$to_free with index $free_at)`");
        return;
    }

    /**
    * @param  T  $to_free
    */
    private function free_by_swapping(int $free_at,  object $to_free) : void
    {
        $highest_nonfree_idx = $this->num_slots_allocated - 1;

        $to_shuffle = $this->slots[$highest_nonfree_idx];

        $this->slots[$free_at] = $to_shuffle;
        $this->assign_element_index($to_shuffle, $free_at);

        $this->slots[$highest_nonfree_idx] = $to_free;
        $this->assign_element_index($to_free, $highest_nonfree_idx);

        $this->num_slots_allocated--;
        return;
    }

    /**
    * @param  T  $element
    */
    public function is_free(object $element) : bool
    {
        $this->debug_invariant_check('before `is_free(...)`');
        $idx = $this->find_element_index($element);
        assert($idx >= 0);
        assert($idx < count($this->slots));
        assert($element === $this->slots[$idx]);
        $result = ($idx >= $this->num_slots_allocated);
        $this->debug_invariant_check('after `is_free(...)`');
        return $result;
    }

    /**
    * @param  T  $to_find
    */
    private function find_element_index(object $to_find) : int
    {
        $result = -1;
        if ( $to_find instanceof Freelist__Indexable ) {
            $result = $to_find->freelist_index();
        } else {
            $result = array_search($to_find,
                array_slice($this->slots, 0, $this->num_slots_allocated), true);
            assert(!is_string($result));
            if ( $result === false ) {
                $result = -1;
            }
        }
        return $result;
    }

    /**
    * @param  T  $element
    */
    private function assign_element_index(object $element, int $idx) : void
    {
        // Giving things an index can make free'ing more efficient later.
        // But it's probably not worth it if the freelist will be small (e.g. <16 elements).
        if ( $element instanceof Freelist__Indexable ) {
            $element->freelist_index($idx);
        }
    }

    private bool $processing_invariants = false;

    private function debug_invariant_check(string $label) : void
    {
        if ($this->debug_mode) {
            $this->try_assert_invariants($label);
        }
    }

    private function try_assert_invariants(string $label) : void
    {
        // Avoid stack recursion when the functions called in the
        // invariant cause the invariants to be invoked which
        // causes those functions to be called which causes
        // the invariants to be invoked which... you know how it goes.
        if ( $this->processing_invariants ) {
            return;
        }

        try {
            $this->processing_invariants = true;
            $this->assert_invariants($label);
        } finally {
            // Make REALLY sure we turn this off, because it could
            // kill all invariant checks and invalidate testing.
            // (Hence the `finally` clause to make sure it always
            // gets executed, even if something throws.)
            $this->processing_invariants = false;
        }
    }

    private function assert_invariants(string $label) : void
    {
        $capacity = count($this->slots);
        $nalloc = $this->num_slots_allocated;

        // All allocated objects must appear in [0, num_slots_allocated)
        for ($i = 0; $i < $nalloc; $i++) {
            assert(isset($this->slots[$i]));
            assert($this->find_element_index($this->slots[$i]) === $i);
        }

        // All free objects should exist beyond that range
        for ($i = $nalloc; $i < $capacity; $i++) {
            assert(isset($this->slots[$i]));
            assert($this->is_free($this->slots[$i]));
        }

        // Indexes are accurate (if indexable)
        for ($i = 0; $i < $capacity; $i++) {
            $obj = $this->slots[$i];
            if ($obj instanceof Freelist__Indexable) {
                assert($obj->freelist_index() === $i);
            }
        }

        // No duplicates in slot array
        $seen = [];
        for ($i = 0; $i < $capacity; $i++) {
            $obj = $this->slots[$i];
            $hash = spl_object_id($obj); // Unique per object instance
            assert(!isset($seen[$hash]), "Duplicate object found at index $i");
            $seen[$hash] = true;
        }
    }

    /** @internal */
    public bool $debug_mode = false;

    // TODO: This testing should probably be a lot better.
    // Right now, this might be more of a component test than a unit test.
    // Maybe that's OK? ¯\_(ツ)_/¯
    // But I do feel it's important to be able to break this stuff down
    // into individual behaviors: just the function being tested and
    // maybe a couple calls that are needed to set things up for it
    // or check results. Then we could have `unittest_allocate`,
    // `unittest_free`, `unittest_recycle`, `unittest_is_free`,
    // and so on.
    //
    // Having a broader test like the one below, possible even longer
    // (as an "endurance" test), is still pretty cool.
    //
    // I'm tempted to use D's freelist implementation as a source of tests:
    // https://github.com/dlang/phobos/blob/master/std/experimental/allocator/building_blocks/free_list.d
    // Though, their freelist is much more native-y and allows you to manage
    // individual bytes for in-place data structures and stuff. It's requirements
    // are... different. So maybe it won't be as helpful as I think.
    private static function unittest_freelist() : void
    {
        $fl = new Freelist();
        $fl->debug_mode = true;

        $a = $fl->allocate(Freelist__TestElement::class);
        $b = $fl->allocate(Freelist__TestElement::class);
        $c = $fl->allocate(Freelist__TestElement::class);

        assert($a instanceof Freelist__TestElement);
        assert($b instanceof Freelist__TestElement);
        assert($c instanceof Freelist__TestElement);

        // Basic identity & index checks
        assert($a !== $b && $b !== $c && $a !== $c);
        assert($a->freelist_index() === 0);
        assert($b->freelist_index() === 1);
        assert($c->freelist_index() === 2);
        assert($a->alloc_count() === 1);
        assert($b->alloc_count() === 1);
        assert($c->alloc_count() === 1);

        // Free + allocate behavior/interactions
        $fl->free($b);
        assert($b->free_count() === 1);

        // Here it should reuse 'b'
        $d = $fl->allocate(Freelist__TestElement::class);
        assert($d === $b);
        assert($d->alloc_count() === 2);
        assert($d->freelist_index() === 2);

        $fl->free($a);
        $fl->free($c);
        $fl->free($d);

        assert($a->free_count() === 1);
        assert($c->free_count() === 1);
        assert($d->free_count() === 2); // b/d got allocated twice

        // Recycle might replace a free'd object,
        // instread returning a different reallocated object.
        // In this case, we make it so.
        $e = $a;
        $was_allocated = $fl->recycle($e);
        assert($was_allocated === false);
        assert($e !== $a); // ($e === $a) before the recycle call, so we managed to obtain the behavior.
        assert($e === $b);
        assert($e !== $c);
        assert($e === $d);
        assert($e->alloc_count() === 3); // TODO: Fix!

        // Check that recycling doesn't reallocate if it doesn't need to
        $was_allocated2 = $fl->recycle($e);
        assert($was_allocated2 === true); // still allocated
        assert($e->alloc_count() === 3);

        $fl->debug_mode = false;
    }

    public static function unittests() : void
    {
        $class_fqn = self::class;
        echo("Running `$class_fqn::unittests()`\n");

        self::unittest_freelist();

        echo("  ... passed.\n\n");
    }
}

interface Freelist__Indexable
{
    public function freelist_index(?int $new_value = null) : int;

    public function on_free() : void;

    public function on_allocate() : void;
}

class Freelist__TestElement implements Freelist__Indexable
{
    private int   $freelist_index_ = -1;
    private int   $free_count_ = 0;
    private int   $alloc_count_ = 0;

    public function free_count()  : int { return $this->free_count_; }
    public function alloc_count() : int { return $this->alloc_count_; }

    public function freelist_index(?int $new_value = null) : int {
        if (isset($new_value)) {
            $this->freelist_index_ = $new_value;
        }
        return $this->freelist_index_;
    }

    public function on_free() : void {
        $this->free_count_++;
    }

    // No action required. The Tester will handle populating our fields.
    public function on_allocate() : void {
        $this->alloc_count_++;
    }
}
?>
