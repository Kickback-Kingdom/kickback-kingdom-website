<?php
declare(strict_types=1);

namespace Kickback\Common\Primitives;

/**
* Extended functionality for the `int` type.
*/
final class Int_
{
    use \Kickback\Common\StaticClassTrait;

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?int          $first_min_index   The lowest 0-based position in the array where the minimum appears.
    * @param-out  int           $first_min_index
    * @param      ?array-key    $first_min_key
    * @param-out  array-key     $first_min_key
    * @param      ?int          $last_min_index    The highest 0-based position in the array where the minimum appears.
    * @param-out  int           $last_min_index
    * @param      ?array-key    $last_min_key
    * @param-out  array-key     $last_min_key
    * @param      ?int          $min_value
    * @param-out  int           $min_value
    *
    * @return (
    *   $arr is non-empty-array<array-key, null> ? false :
    *   $arr is non-empty-array<array-key, ?int> ? true  : false)
    */
    public static function min_pair(
        array $arr,
        ?int &$first_min_index, int|string|null &$first_min_key,
        ?int &$last_min_index,  int|string|null &$last_min_key,
        ?int &$min_value) : bool
    {
        return Mixed_::min_pair($arr,
            $first_min_index, $first_min_key,
            $last_min_index,  $last_min_key,
            $min_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $min_key
    * @param-out  ?array-key              $min_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function first_min_pair(array $arr, int|string|null &$min_key,  int &$min_value) : int
    {
        return Mixed_::first_min_pair($arr, $min_key, $min_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $min_key
    * @param-out  array-key               $min_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function last_min_pair(array $arr, int|string|null &$min_key,  int &$min_value) : int
    {
        return Mixed_::last_min_pair($arr, $min_key, $min_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?int          $first_max_index   The lowest 0-based position in the array where the maximum appears.
    * @param-out  int           $first_max_index
    * @param      ?array-key    $first_max_key
    * @param-out  array-key     $first_max_key
    * @param      ?int          $last_max_index    The highest 0-based position in the array where the maximum appears.
    * @param-out  int           $last_max_index
    * @param      ?array-key    $last_max_key
    * @param-out  array-key     $last_max_key
    * @param      ?int          $max_value
    * @param-out  int           $max_value
    *
    * @return (
    *   $arr is non-empty-array<array-key, null> ? false :
    *   $arr is non-empty-array<array-key, ?int> ? true  : false)
    */
    public static function max_pair(
        array $arr,
        ?int &$first_max_index, int|string|null &$first_max_key,
        ?int &$last_max_index,  int|string|null &$last_max_key,
        ?int &$max_value) : bool
    {
        return Mixed_::max_pair($arr,
            $first_max_index, $first_max_key,
            $last_max_index,  $last_max_key,
            $max_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $max_key
    * @param-out  array-key               $max_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function first_max_pair(array $arr, int|string|null &$max_key,  int &$max_value) : int
    {
        return Mixed_::first_max_pair($arr, $max_key, $max_value);
    }

    /**
    * @param      array<array-key, ?int>  $arr
    * @param      ?array-key              $max_key
    * @param-out  array-key               $max_key
    *
    * @return int  The 0-based position in the array where the minimum occurs.
    */
    public static function last_max_pair(array $arr, int|string|null &$max_key,  int &$max_value) : int
    {
        return Mixed_::last_max_pair($arr, $max_key, $max_value);
    }
}
?>
