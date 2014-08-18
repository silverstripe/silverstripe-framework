<?php

/**
 * Library of static methods for manipulating arrays.
 *
 * @package framework
 * @subpackage misc
 */
class ArrayLib {

	/**
	 * Inverses the first and second level keys of an associative
	 * array, keying the result by the second level, and combines
	 * all first level entries within them.
	 *
	 * Before:
	 * <example>
	 * array(
	 * 	'row1' => array(
	 * 		'col1' =>'val1',
	 * 		'col2' => 'val2'
	 * 	),
	 * 	'row2' => array(
	 * 		'col1' => 'val3',
	 * 		'col2' => 'val4'
	 * 	)
	 * )
	 * </example>
	 *
	 * After:
	 * <example>
	 * array(
	 * 	'col1' => array(
	 * 		'row1' => 'val1',
	 * 		'row2' => 'val3',
	 * 	),
	 * 	'col2' => array(
	 * 		'row1' => 'val2',
	 * 		'row2' => 'val4',
	 * 	),
	 * )
	 * </example>
	 *
	 * @param array $arr
	 * @return array
	 */
	public static function invert($arr) {
		if(!$arr) {
			return false;
		}

		$result = array();

		foreach($arr as $columnName => $column) {
			foreach($column as $rowName => $cell) {
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
	 */
	public static function valuekey($arr) {
		return array_combine($arr, $arr);
	}

	/**
	 * @todo Improve documentation
	 */
	public static function array_values_recursive($arr) {
		$lst = array();

		foreach(array_keys($arr) as $k) {
			$v = $arr[$k];
			if (is_scalar($v)) {
				$lst[] = $v;
			} elseif (is_array($v)) {
				$lst = array_merge( $lst,
					self::array_values_recursive($v)
				);
			}
		}

		return $lst;
	}

	/**
	 * Filter an array by keys (useful for only allowing certain form-input to
	 * be saved).
	 *
	 * @param $arr array
	 * @param $keys array
	 *
	 * @return array
	 */
	public static function filter_keys($arr, $keys) {
		foreach($arr as $key => $v) {
			if(!in_array($key, $keys)) {
				unset($arr[$key]);
			}
		}

		return $arr;
	}

	/**
	 * Determines if an array is associative by checking for existing keys via
	 * array_key_exists().
	 *
	 * @see http://nz.php.net/manual/en/function.is-array.php#76188
	 *
	 * @param array $arr
	 *
	 * @return boolean
	 */
	public static function is_associative($arr) {
		if(is_array($arr) && ! empty($arr)) {
			for($iterator = count($arr) - 1; $iterator; $iterator--) {
				if (!array_key_exists($iterator, $arr)) {
					return true;
				}
			}

			return !array_key_exists(0, $arr);
		}

		return false;
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
	 */
	public static function in_array_recursive($needle, $haystack, $strict = false) {
		if(!is_array($haystack)) {
			return false;
		}

		if(in_array($needle, $haystack, $strict)) {
			return true;
		} else {
			foreach($haystack as $obj) {
				if(self::in_array_recursive($needle, $obj, $strict)) {
					return true;
				}
			}
		}

		return false;
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
	 */
	public static function array_merge_recursive($array) {
		$arrays = func_get_args();
		$merged = array();

		if(count($arrays) == 1) {
			return $array;
		}

		while ($arrays) {
			$array = array_shift($arrays);

			if (!is_array($array)) {
				trigger_error('ArrayLib::array_merge_recursive() encountered a non array argument', E_USER_WARNING);
				return;
			}

			if (!$array) {
				continue;
			}

			foreach ($array as $key => $value) {
				if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
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
	 *
	 * @return array
	 */
	public static function flatten($array, $preserveKeys = true, &$out = array()) {
		foreach($array as $key => $child) {
			if(is_array($child)) {
				$out = self::flatten($child, $preserveKeys, $out);
			} else if($preserveKeys) {
				$out[$key] = $child;
			} else {
				$out[] = $child;
			}
		}

		return $out;
	}
}

