<?php

namespace SilverStripe\ORM;

use Generator;
use SilverStripe\Dev\Deprecation;
use InvalidArgumentException;

/**
 * Library of static methods for manipulating arrays.
 * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib
 */
class ArrayLib
{
    public function __construct()
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib', Deprecation::SCOPE_CLASS);
        });
    }

    /**
     * Inverses the first and second level keys of an associative
     * array, keying the result by the second level, and combines
     * all first level entries within them.
     *
     * Before:
     * <example>
     * array(
     *    'row1' => array(
     *        'col1' =>'val1',
     *        'col2' => 'val2'
     *    ),
     *    'row2' => array(
     *        'col1' => 'val3',
     *        'col2' => 'val4'
     *    )
     * )
     * </example>
     *
     * After:
     * <example>
     * array(
     *    'col1' => array(
     *        'row1' => 'val1',
     *        'row2' => 'val3',
     *    ),
     *    'col2' => array(
     *        'row1' => 'val2',
     *        'row2' => 'val4',
     *    ),
     * )
     * </example>
     *
     * @param array $arr
     * @return array
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::invert()
     */
    public static function invert($arr)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::invert()');
        });

        if (!$arr) {
            return [];
        }

        $result = [];

        foreach ($arr as $columnName => $column) {
            foreach ($column as $rowName => $cell) {
                $result[$rowName][$columnName] = $cell;
            }
        }

        return $result;
    }

    /**
     * Return an array where the keys are all equal to the values.
     *
     * @param $arr array
     * @return array
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::valuekey()
     */
    public static function valuekey($arr)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::valuekey()');
        });

        return array_combine($arr ?? [], $arr ?? []);
    }

    /**
     * Flattens a multi-dimensional array to a one level array without preserving the keys
     *
     * @param array $array
     * @return array
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::array_values_recursive()
     */
    public static function array_values_recursive($array)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::invearray_values_recursivert()');
        });

        return ArrayLib::flatten($array, false);
    }

    /**
     * Filter an array by keys (useful for only allowing certain form-input to
     * be saved).
     *
     * @param $arr array
     * @param $keys array
     *
     * @return array
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::filter_keys()
     */
    public static function filter_keys($arr, $keys)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::filter_keys()');
        });

        foreach ($arr as $key => $v) {
            if (!in_array($key, $keys ?? [])) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    /**
     * Determines if an array is associative by checking for existing keys via
     * array_key_exists().
     *
     * @see http://nz.php.net/manual/en/function.is-array.php#121692
     *
     * @param array $array
     *
     * @return boolean
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::is_associative()
     */
    public static function is_associative($array)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::is_associative()');
        });

        $isAssociative = !empty($array)
            && is_array($array)
            && ($array !== array_values($array ?? []));

        return $isAssociative;
    }

    /**
     * Recursively searches an array $haystack for the value(s) $needle.
     *
     * Assumes that all values in $needle (if $needle is an array) are at
     * the SAME level, not spread across multiple dimensions of the $haystack.
     *
     * @param mixed $needle
     * @param array $haystack
     * @param boolean $strict
     *
     * @return boolean
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::in_array_recursive()
     */
    public static function in_array_recursive($needle, $haystack, $strict = false)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::in_array_recursive()');
        });

        if (!is_array($haystack)) {
            return false;
        }

        if (in_array($needle, $haystack ?? [], $strict ?? false)) {
            return true;
        } else {
            foreach ($haystack as $obj) {
                if (ArrayLib::in_array_recursive($needle, $obj, $strict)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Similar to array_map, but recurses when arrays are encountered.
     *
     * Actually only one array argument is supported.
     *
     * @param $f callback to apply
     * @param $array array
     * @return array
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::array_map_recursive()
     */
    public static function array_map_recursive($f, $array)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::array_map_recursive()');
        });

        $applyOrRecurse = function ($v) use ($f) {
            return is_array($v) ? ArrayLib::array_map_recursive($f, $v) : call_user_func($f, $v);
        };

        return array_map($applyOrRecurse, $array ?? []);
    }

    /**
     * Recursively merges two or more arrays.
     *
     * Behaves similar to array_merge_recursive(), however it only merges
     * values when both are arrays rather than creating a new array with
     * both values, as the PHP version does. The same behaviour also occurs
     * with numeric keys, to match that of what PHP does to generate $_REQUEST.
     *
     * @param array $array
     *
     * @return array
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::array_merge_recursive()
     */
    public static function array_merge_recursive($array)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::array_merge_recursive()');
        });

        $arrays = func_get_args();
        $merged = [];

        if (count($arrays ?? []) == 1) {
            return $array;
        }

        while ($arrays) {
            $array = array_shift($arrays);

            if (!is_array($array)) {
                trigger_error(
                    'SilverStripe\ORM\ArrayLib::array_merge_recursive() encountered a non array argument',
                    E_USER_WARNING
                );
                return [];
            }

            if (!$array) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_array($value) && array_key_exists($key, $merged ?? []) && is_array($merged[$key])) {
                    $merged[$key] = ArrayLib::array_merge_recursive($merged[$key], $value);
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Takes an multi dimension array and returns the flattened version.
     *
     * @param array $array
     * @param boolean $preserveKeys
     * @param array $out
     *
     * @return array
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::flatten()
     */
    public static function flatten($array, $preserveKeys = true, &$out = [])
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::flatten()');
        });

        array_walk_recursive(
            $array,
            function ($value, $key) use (&$out, $preserveKeys) {
                if (!is_scalar($value)) {
                    // Do nothing
                } elseif ($preserveKeys) {
                    $out[$key] = $value;
                } else {
                    $out[] = $value;
                }
            }
        );

        return $out;
    }

    /**
     * Iterate list, but allowing for modifications to the underlying list.
     * Items in $list will only be iterated exactly once for each key, and supports
     * items being removed or deleted.
     * List must be associative.
     *
     * @param array $list
     * @return Generator
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::iterateVolatile()
     */
    public static function iterateVolatile(array &$list)
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::iterateVolatile()');
        });

        // Keyed by already-iterated items
        $iterated = [];
        // Get all items not yet iterated
        while ($items = array_diff_key($list ?? [], $iterated)) {
            // Yield all results
            foreach ($items as $key => $value) {
                // Skip items removed by a prior step
                if (array_key_exists($key, $list ?? [])) {
                    // Ensure we yield from the source list
                    $iterated[$key] = true;
                    yield $key => $list[$key];
                }
            }
        }
    }

    /**
     * Similar to shuffle, but retains the existing association between the keys and the values.
     * Shuffles the array in place.
     * @deprecated 5.4.0 Will be renamed to SilverStripe\Core\ArrayLib::shuffleAssociative()
     */
    public static function shuffleAssociative(array &$array): void
    {
        Deprecation::withSuppressedNotice(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Core\ArrayLib::shuffleAssociative()');
        });

        $shuffledArray = [];
        $keys = array_keys($array);
        shuffle($keys);

        foreach ($keys as $key) {
            $shuffledArray[$key] = $array[$key];
        }

        $array = $shuffledArray;
    }

    /**
     * Insert a value into an array before another given value.
     * Does not preserve keys.
     *
     * @param mixed $before The value to check for. If this value isn't in the source array, $insert will be put at the end.
     * @param boolean $strict If true then this will perform a strict type comparison to look for the $before value in the source array.
     * @param boolean $splatInsertArray If true, $insert must be an array.
     * Its values will be splatted into the source array.
     */
    public static function insertBefore(array $array, mixed $insert, mixed $before, bool $strict = false, bool $splatInsertArray = false): array
    {
        if ($splatInsertArray && !is_array($insert)) {
            throw new InvalidArgumentException('$insert must be an array when $splatInsertArray is true. Got ' . gettype($insert));
        }
        $array = array_values($array);
        $pos = array_search($before, $array, $strict);
        if ($pos === false) {
            return static::insertIntoArray($array, $insert, $splatInsertArray);
        }
        return static::insertAtPosition($array, $insert, $pos, $splatInsertArray);
    }

    /**
     * Insert a value into an array after another given value.
     * Does not preserve keys.
     *
     * @param mixed $after The value to check for. If this value isn't in the source array, $insert will be put at the end.
     * @param boolean $strict If true then this will perform a strict type comparison to look for the $before value in the source array.
     * @param boolean $splatInsertArray If true, $insert must be an array.
     * Its values will be splatted into the source array.
     */
    public static function insertAfter(array $array, mixed $insert, mixed $after, bool $strict = false, bool $splatInsertArray = false): array
    {
        if ($splatInsertArray && !is_array($insert)) {
            throw new InvalidArgumentException('$insert must be an array when $splatInsertArray is true. Got ' . gettype($insert));
        }
        $array = array_values($array);
        $pos = array_search($after, $array, $strict);
        if ($pos === false) {
            return static::insertIntoArray($array, $insert, $splatInsertArray);
        }
        return static::insertAtPosition($array, $insert, $pos + 1, $splatInsertArray);
    }

    private static function insertAtPosition(array $array, mixed $insert, int $pos, bool $splatInsertArray): array
    {
        $result = array_slice($array, 0, $pos);
        $result = static::insertIntoArray($result, $insert, $splatInsertArray);
        return array_merge($result, array_slice($array, $pos));
    }

    private static function insertIntoArray(array $array, mixed $insert, bool $splatInsertArray): array
    {
        if ($splatInsertArray) {
            $array = array_merge($array, $insert);
        } else {
            $array[] = $insert;
        }
        return $array;
    }
}
