<?php
/**
 * A list decorator that allows a list to be grouped into sub-lists by common
 * values of a field.
 *
 * @package framework
 * @subpackage model
 */
class GroupedList extends SS_ListDecorator {

	/**
	 * @param  string $index
	 * @return ArrayList
	 */
	public function groupBy($index) {
		$result = array();

		foreach ($this->list as $item) {
			$key = is_object($item) ? $item->$index : $item[$index];

			if (array_key_exists($key, $result)) {
				$result[$key]->push($item);
			} else {
				$result[$key] = new ArrayList(array($item));
			}
		}

		return $result;
	}

	/**
	 * @param  string $index
	 * @param  string $children
	 * @return ArrayList
	 */
	public function GroupedBy($index, $children = 'Children') {
		$grouped = $this->groupBy($index);
		$result  = new ArrayList();

		foreach ($grouped as $indVal => $list) {
			$result->push(new ArrayData(array(
				$index    => $indVal,
				$children => $list
			)));
		}

		return $result;
	}

}
