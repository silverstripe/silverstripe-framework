<?php

/**
 * @package sapphire
 * @subpackage misc
 */

/**
 * Library of static methods for manipulating arrays.
 * @package sapphire
 * @subpackage misc
 */
class ArrayLib extends Object {
	static function invert($arr) {
		if (! $arr) return false;
		
		foreach($arr as $columnName => $column) {
			foreach($column as $rowName => $cell) {
				$output[$rowName][$columnName] = $cell;
			}
		}
		return $output;
	}
	
	/**
	 * Return an array where the keys are all equal to the values
	 * 
	 * @param $arr array
	 * @return array
	 */
	static function valuekey($arr) {
		foreach($arr as $val) {
			$newArr[$val] = $val;
		}
		return $newArr;
	}
	
	static function array_values_recursive($arr) {
	   $lst = array();
	   foreach(array_keys($arr) as $k){
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
	 * Filter an array by keys (useful for only allowing certain form-input to be saved).
	 * 
	 * @param $arr array
	 * @param $keys array
	 * @return array
	 */
	static function filter_keys($arr, $keys)
	{
		foreach ($arr as $key => $v) {
			if (!in_array($key, $keys)) {
				unset($arr[$key]);
			}
		}
		return $arr;
	}
	
	/**
	 * Determines if an array is associative by checking
	 * for existing keys via array_key_exists().
	 * @see http://nz.php.net/manual/en/function.is-array.php#76188
	 *
	 * @param array $arr
	 * @return boolean
	 */
	static function is_associative($arr) {
		if(is_array($arr) && ! empty($arr)) {
	        for($iterator = count($arr) - 1; $iterator; $iterator--) {
	            if (!array_key_exists($iterator, $arr)) return true;
	        }
	        return !array_key_exists(0, $arr);
	    }
    	return false;
	}
}

?>