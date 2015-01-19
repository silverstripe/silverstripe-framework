<?php
/**
 * A list decorator that allows a list to be grouped into sub-lists
 * by a given field, or related object.
 *
 * @package framework
 * @subpackage model
 */
class GroupedList extends SS_ListDecorator {

	/**
	 * Store references to has_one object groupings,
	 * if present.
	 * @var array
	 */
	protected $groupobjects = array();

	/**
	 * @param  string $index
	 * @return array
	 */
	public function groupBy($index) {
		$result = array();

		foreach ($this->list as $item) {
			// if $item is an Object, $index can be a method or a value,
			// if $item is an array, $index is used as the index
			$key = is_object($item) ? ($item->hasMethod($index) ? $item->$index() : $item->$index) : $item[$index];

			//convert index relation object into ID
			if($key instanceof DataObject){
				$this->groupobjects[$key->ID] = $key;
				$key = $key->ID;
			}

			if (array_key_exists($key, $result)) {
				$result[$key]->push($item);
			} else {
				$result[$key] = new ArrayList(array($item));
			}
		}

		return $result;
	}

	/**
	 * Similar to {@link groupBy()}, but returns
	 * the data in a format which is suitable for usage in templates.
	 *
	 * @param  string $index
	 * @param  string $children Name of the control under which children can be iterated on
	 * @return ArrayList
	 */
	public function GroupedBy($index, $children = 'Children') {
		$grouped = $this->groupBy($index);
		$result  = new ArrayList();
		foreach ($grouped as $indVal => $list) {
			$list = GroupedList::create($list);
			//convert indVal from ID to DataObject, if appropriate
			if(!empty($this->groupobjects) && isset($this->groupobjects[$indVal])){
				$indVal = $this->groupobjects[$indVal];
			}
			$result->push(new ArrayData(array(
				$index    => $indVal,
				$children => $list
			)));
		}

		return $result;
	}

}
