<?php
/**
 * This class represents a set of {@link ViewableData} subclasses (mostly {@link DataObject} or {@link ArrayData}).
 * It is used by the ORM-layer of Silverstripe to return query-results from {@link SQLQuery}.
 * @package sapphire
 * @subpackage model
 */
class DataObjectSet extends ViewableData implements IteratorAggregate, Countable, ArrayAccess {
	/**
	 * The DataObjects in this set.
	 * @var array
	 */
	protected $items = array();
	
	protected $odd = 0;
	
	/**
	 * True if the current DataObject is the first in this set.
	 * @var boolean
	 */
	protected $first = true;
	
	/**
	 * True if the current DataObject is the last in this set.
	 * @var boolean
	 */
	protected $last = false;
	
	/**
	 * The current DataObject in this set.
	 * @var DataObject
	 */
	protected $current = null;

	/**
	 * Create a new DataObjectSet. If you pass one or more arguments, it will try to convert them into {@link ArrayData} objects. 
	 * @todo Does NOT automatically convert objects with complex datatypes (e.g. converting arrays within an objects to its own DataObjectSet)							
	 * 
	 * @param ViewableData|array|mixed $items Parameters to use in this set, either as an associative array, object with simple properties, or as multiple parameters.
	 */
	public function __construct($items = null) {
		if($items) {
			// if the first parameter is not an array, or we have more than one parameter, collate all parameters to an array
			// otherwise use the passed array
			$itemsArr = (!is_array($items) || count(func_get_args()) > 1) ? func_get_args() : $items;
			
			// We now have support for using the key of a data object set
			foreach($itemsArr as $i => $item) {
				if(is_subclass_of($item, 'ViewableData')) {
					$this->items[$i] = $item;
				} elseif(is_object($item) || ArrayLib::is_associative($item)) {
					$this->items[$i] = new ArrayData($item);
				} else {
					user_error(
						"DataObjectSet::__construct: Passed item #{$i} is not an object or associative array, 
						can't be properly iterated on in templates", 
						E_USER_WARNING
					);						
					$this->items[$i] = $item;
				}
			}
		}
		parent::__construct();
	}
	
	/**
	 * Necessary for interface ArrayAccess. Returns whether an item with $key exists
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($key) {
		return isset($this->items[$key]);
	}

	/**
	 * Necessary for interface ArrayAccess. Returns item stored in array with index $key
	 * @param mixed $key
	 * @return DataObject
	 */
	public function offsetGet($key) {
		return $this->items[$key];
	}
	
	/**
	 * Necessary for interface ArrayAccess. Set an item with the key in $key
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value) {
		$this->items[$key] = $value;
	}

	/**
	 * Necessary for interface ArrayAccess. Unset an item with the key in $key
	 * @param mixed $key
	 */
	public function offsetUnset($key) {
		unset($this->items[$key]);
	}
	
	/**
	 * Destory all of the DataObjects in this set.
	 */
	public function destroy() {
		foreach($this as $item) {
			$item->destroy();
		}
	}
	
	/**
	 * Removes all the items in this set.
	 */
	public function emptyItems() {
		$this->items = array();
	}
	
	/**
	 * Convert this DataObjectSet to an array of DataObjects.
	 * @param string $index Index the array by this field.
	 * @return array
	 */
	public function toArray($index = null) {
		$map = array();
		foreach($this as $item) {
			if($index) $map[$item->$index] = $item;
			else $map[] = $item;
		}
			
		return $map;
	}

	/**
	* Convert this DataObjectSet to an array of maps.
	* @param string $index Index the array by this field.
	* @return array
	*/
	public function toNestedArray($index = null){
		if(!$index) {
			$index = "ID";
		}
		
		$map = array();
		
		foreach( $this as $item ) {
			$map[$item->$index] = $item->getAllFields();
		}
		
		return $map;
	}

	/**
	 * Returns an array of ID => Title for the items in this set.
	 * 
	 * This is an alias of {@link DataObjectSet->map()}
	 * 
	 * @deprecated 2.5 Please use map() instead
	 * 
	 * @param string $index The field to use as a key for the array
	 * @param string $titleField The field (or method) to get values for the map
	 * @param string $emptyString Empty option text e.g "(Select one)"
	 * @param bool $sort Sort the map alphabetically based on the $titleField value
	 * @return array
	 */
	public function toDropDownMap($index = 'ID', $titleField = 'Title', $emptyString = null, $sort = false) {
		return $this->map($index, $titleField, $emptyString, $sort);
	}

	/**
	 * Add an item to the DataObject Set.
	 * @param DataObject $item Item to add.
	 * @param string $key Key to index this DataObject by.
	 */
	public function push($item, $key = null) {
		if($key != null) {
			unset($this->items[$key]);
			$this->items[$key] = $item;
		} else {
			$this->items[] = $item;
		}
	}
    
	/**
	 * Add an item to the beginning of the DataObjectSet
	 * @param DataObject $item Item to add
	 * @param string $key Key to index this DataObject by.
	 */
	public function insertFirst($item, $key = null) {
		if($key == null) {
			array_unshift($this->items, $item);
		} else {
			$this->items = array_merge(array($key=>$item), $this->items); 
		}
	}

	/**
	 * Insert a DataObject at the beginning of this set.
	 * @param DataObject $item Item to insert.
	 */
	public function unshift($item) {
		$this->insertFirst($item);
	}

	/**
	 * Remove a DataObject from the beginning of this set and return it.
	 * This is the equivalent of pop() but acts on the head of the set.
	 * Opposite of unshift().
	 * 
	 * @return DataObject (or null if there are no items in the set)
	 */
	public function shift() {
		return array_shift($this->items);
	}

	/**
	 * Remove a DataObject from the end of this set and return it.
	 * This is the equivalent of shift() but acts on the tail of the set.
	 * Opposite of push().
	 * 
	 * @return DataObject (or null if there are no items in the set)
	 */
	public function pop() {
		return array_pop($this->items);
	}

	/**
	 * Remove a DataObject from this set.
	 * @param DataObject $itemObject Item to remove.
	 */
	public function remove($itemObject) {
		foreach($this->items as $key=>$item){
			if($item === $itemObject){
				unset($this->items[$key]);
			}
		}
	}
	
	/**
	 * Replaces $itemOld with $itemNew
	 *
	 * @param DataObject $itemOld
	 * @param DataObject $itemNew
	 */	
	public function replace($itemOld, $itemNew) {
		foreach($this->items as $key => $item) {
			if($item === $itemOld) {
				$this->items[$key] = $itemNew;
				return;
			}
		}
	}
	
	/**
	 * Merge another set onto the end of this set.
	 * To merge without causing duplicates, consider calling
	 * {@link removeDuplicates()} after this method on the new set.
	 * 
	 * @param DataObjectSet $anotherSet Set to mege onto this set.
	 */
	public function merge($anotherSet){
		if($anotherSet) {
			foreach($anotherSet as $item){
				$this->push($item);
			}
		}
	}

	/**
	 * Gets a specific slice of an existing set.
	 * 
	 * @param int $offset
	 * @param int $length
	 * @return DataObjectSet
	 */
	public function getRange($offset, $length) {
		$set = array_slice($this->items, (int)$offset, (int)$length);
		return new DataObjectSet($set);
	}

	/**
	 * Returns an Iterator for this DataObjectSet.
	 * This function allows you to use DataObjectSets in foreach loops
	 * @return DataObjectSet_Iterator
	 */
	public function getIterator() {
		return new DataObjectSet_Iterator($this->items);
	}
	
	/**
	 * Returns false if the set is empty.
	 * @return boolean
	 */
	public function exists() {
		return $this->count() > 0;
	}
	
	/**
	 * Return the first item in the set.
	 * @return DataObject
	 */
	public function First() {
		if(count($this->items) < 1)
			return null;

		$keys = array_keys($this->items);
		return $this->items[$keys[0]];
	}
	
	/**
	 * Return the last item in the set.
	 * @return DataObject
	 */
	public function Last() {
		if(count($this->items) < 1)
			return null;

		$keys = array_keys($this->items);
		return $this->items[$keys[sizeof($keys)-1]];
	}

	/**
	 * @deprecated 3.0 Use {@link DataObjectSet::Count()}.
	 */
	public function TotalItems() {
		return $this->Count();
	}
	
	/**
	 * Returns the actual number of items in this dataset.
	 * @return int
	 */
	public function Count() {
		return sizeof($this->items);
	}
	
	/**
	 * Returns this set as a XHTML unordered list.
	 * @return string
	 */
	public function UL() {
		if($this->exists()) {
			$result = "<ul id=\"Menu1\">\n";
			foreach($this as $item) {
				$result .= "<li onclick=\"location.href = this.getElementsByTagName('a')[0].href\"><a href=\"$item->Link\">$item->Title</a></li>\n";
			}
			$result .= "</ul>\n";

			return $result;
		}
	}
	
	/**
	 * Returns this set as a XHTML unordered list.
	 * @return string
	 */
	public function forTemplate() {
		return $this->UL();
	}

	/**
	 * Returns an array of ID => Title for the items in this set.
	 * 
	 * @param string $index The field to use as a key for the array
	 * @param string $titleField The field (or method) to get values for the map
	 * @param string $emptyString Empty option text e.g "(Select one)"
	 * @param bool $sort Sort the map alphabetically based on the $titleField value
	 * @return array
	 */
	public function map($index = 'ID', $titleField = 'Title', $emptyString = null, $sort = false) {
		$map = array();
		foreach($this as $item) {
			$map[$item->$index] = ($item->hasMethod($titleField)) 
				? $item->$titleField() : $item->$titleField;
		}
		if($emptyString) $map = array('' => $emptyString) + $map;
		
		if($sort) asort($map);
		
		return $map;
	}
    
    /**
     * Find an item in this list where the field $key is equal to $value
     * Eg: $doSet->find('ID', 4);
     * @return ViewableData The first matching item.
     */
    public function find($key, $value) {
        foreach($this as $item) {
            if($item->$key == $value) return $item;
        }
    }
	
	/**
	 * Return a column of the given field
	 * @param string $value The field name
	 * @return array
	 */
	public function column($value = "ID") {
		$list = array();
		foreach($this as $item ){
			$list[] = ($item->hasMethod($value)) ? $item->$value() : $item->$value;
		}
		return $list;
	}

	/**
	 * Returns an array of DataObjectSets. The array is keyed by index.
	 * 
	 * @param string $index The field name to index the array by.
	 * @return array
	 */
	public function groupBy($index){
		foreach($this as $item ){
			$key = ($item->hasMethod($index)) ? $item->$index() : $item->$index;
			if(!isset($result[$key])) {
				$result[$key] = new DataObjectSet();
			}
			$result[$key]->push($item);
		}
		return $result;
	}

	/**
	 * Groups the items by a given field.
	 * Returns a DataObjectSet suitable for use in a nested template.
	 * @param string $index The field to group by
	 * @param string $childControl The name of the nested page control
	 * @return DataObjectSet
	 */
	public function GroupedBy($index, $childControl = "Children") {
		$grouped = $this->groupBy($index);
		$groupedAsSet = new DataObjectSet();
		foreach($grouped as $group) {
			$groupedAsSet->push($group->First()->customise(array(
				$childControl => $group
			)));
		}
		return $groupedAsSet;
	}

	/**
	 * Returns a nested unordered list out of a "chain" of DataObject-relations,
	 * using the automagic ComponentSet-relation-methods to find subsequent DataObjectSets.
	 * The formatting of the list can be different for each level, and is evaluated as an SS-template
	 * with access to the current DataObjects attributes and methods.
	 *
	 * Example: Groups (Level 0, the "calling" DataObjectSet, needs to be queried externally)
	 * and their Members (Level 1, determined by the Group->Members()-relation).
	 * 
	 * @param array $nestingLevels
	 * Defines relation-methods on DataObjects as a string, plus custom
	 * SS-template-code for the list-output. Use "Root" for the current DataObjectSet (is will not evaluate into
	 * a function).
	 * Caution: Don't close the list-elements (determined programatically).
	 * You need to escape dollar-signs that need to be evaluated as SS-template-code.
	 * Use $EvenOdd to get appropriate classes for CSS-styling.
	 * Format:
	 * array(
	 * 	array(
	 * 		"dataclass" => "Root",
	 * 		"template" => "<li class=\"\$EvenOdd\"><a href=\"admin/crm/show/\$ID\">\$AccountName</a>"
	 * 	),
	 * 	array(
	 * 		"dataclass" => "GrantObjects",
	 * 		"template" => "<li class=\"\$EvenOdd\"><a href=\"admin/crm/showgrant/\$ID\">#\$GrantNumber: \$TotalAmount.Nice, \$ApplicationDate.ShortMonth \$ApplicationDate.Year</a>"
	 * 	)
	 * );
	 * @param string $ulExtraAttributes Extra attributes
	 * 
	 * @return string Unordered List (HTML)
	 */
	public function buildNestedUL($nestingLevels, $ulExtraAttributes = "") {
		return $this->getChildrenAsUL($nestingLevels, 0, "", $ulExtraAttributes);
	}

	/**
	 * Gets called recursively on the child-objects of the chain.
	 * 
	 * @param array $nestingLevels see {@buildNestedUL}
	 * @param int $level Current nesting level
	 * @param string $template Template for list item
	 * @param string $ulExtraAttributes Extra attributes
	 * @return string
	 */
	public function getChildrenAsUL($nestingLevels, $level = 0, $template = "<li id=\"record-\$ID\" class=\"\$EvenOdd\">\$Title", $ulExtraAttributes = null, &$itemCount = 0) {
		$output = "";
		$hasNextLevel = false;
		$ulExtraAttributes = " $ulExtraAttributes";
		$output = "<ul" . eval($ulExtraAttributes) . ">\n";

		$currentNestingLevel = $nestingLevels[$level];

		// either current or default template
		$currentTemplate = (!empty($currentNestingLevel)) ? $currentNestingLevel['template'] : $template;
		$myViewer = SSViewer::fromString($currentTemplate);

		if(isset($nestingLevels[$level+1]['dataclass'])){
			$childrenMethod = $nestingLevels[$level+1]['dataclass'];
		}
		// sql-parts

		$filter = (isset($nestingLevels[$level+1]['filter'])) ? $nestingLevels[$level+1]['filter'] : null;
		$sort = (isset($nestingLevels[$level+1]['sort'])) ? $nestingLevels[$level+1]['sort'] : null;
		$join = (isset($nestingLevels[$level+1]['join'])) ? $nestingLevels[$level+1]['join'] : null;
		$limit = (isset($nestingLevels[$level+1]['limit'])) ? $nestingLevels[$level+1]['limit'] : null;
		$having = (isset($nestingLevels[$level+1]['having'])) ? $nestingLevels[$level+1]['having'] : null;

		foreach($this as $parent) {
			$evenOdd = ($itemCount % 2 == 0) ? "even" : "odd";
			$parent->setField('EvenOdd', $evenOdd);
			$template = $myViewer->process($parent);

			// if no output is selected, fall back to the id to keep the item "clickable"
			$output .= $template . "\n";

			if(isset($childrenMethod)) {
				// workaround for missing groupby/having-parameters in instance_get
				// get the dataobjects for the next level
				$children = $parent->$childrenMethod($filter, $sort, $join, $limit, $having);
				if($children) {
					$output .= $children->getChildrenAsUL($nestingLevels, $level+1, $currentTemplate, $ulExtraAttributes);
				}
			}
			$output .= "</li>\n";
			$itemCount++;
		}

		$output .= "</ul>\n";

		return $output;
	}

	/**
	* Sorts the current DataObjectSet instance.
	* @param string $fieldname The name of the field on the DataObject that you wish to sort the set by.
	* @param string $direction Direction to sort by, either "ASC" or "DESC".
	*/
	public function sort($fieldname, $direction = "ASC") {
		if($this->items) {
			if (is_string($fieldname)  &&  preg_match('/(.+?)(\s+?)(A|DE)SC$/', $fieldname, $matches)) {
				$fieldname = $matches[1];
				$direction = $matches[3].'SC';
			}
			column_sort($this->items, $fieldname, $direction, false);
		}
	}

	/**
	* Remove duplicates from this set based on the dataobjects field.
	* Assumes all items contained in the set all have that field.
	* Useful after merging to sets via {@link merge()}.
	* 
	* @param string $field the field to check for duplicates
	*/
	public function removeDuplicates($field = 'ID') {
		$exists = array();
		foreach($this->items as $key => $item) {
			if(isset($exists[$fullkey = ClassInfo::baseDataClass($item) . ":" . $item->$field])) {
				unset($this->items[$key]);
			}
			$exists[$fullkey] = true;
		}
	}
	
	/**
	 * Returns information about this set in HTML format for debugging.
	 * @return string
	 */
	public function debug() {
		$val = "<h2>" . $this->class . "</h2><ul>";
		foreach($this as $item) {
			$val .= "<li style=\"list-style-type: disc; margin-left: 20px\">" . Debug::text($item) . "</li>";
		}
		$val .= "</ul>";
		return $val;
	}

	/**
	 * Groups the set by $groupField and returns the parent of each group whose class
	 * is $groupClassName. If $collapse is true, the group will be collapsed up until an ancestor with the
	 * given class is found.
	 * @param string $groupField The field to group by.
	 * @param string $groupClassName Classname.
	 * @param string $sortParents SORT clause to insert into the parents SQL.
	 * @param string $parentField Parent field.
	 * @param boolean $collapse Collapse up until an ancestor with the given class is found.
	 * @param string $requiredParents Required parents
	 * @return DataObjectSet
	 */
	public function groupWithParents($groupField, $groupClassName, $sortParents = null, $parentField = 'ID', $collapse = false, $requiredParents = null) {
		$groupTable = ClassInfo::baseDataClass($groupClassName);
		
		// Each item in this DataObjectSet is grouped into a multidimensional array
		// indexed by it's parent. The parent IDs are later used to find the parents
		// that make up the returned set.
		$groupedSet = array();

		// Array to store the subgroups matching the requirements
		$resultsArray = array();

		// Put this item into the array indexed by $groupField.
		// the keys are later used to retrieve the top-level records
		foreach( $this as $item ) {
			$groupedSet[$item->$groupField][] = $item;
		}

		$parentSet = null;

		// retrieve parents for this set

		// TODO How will we collapse the hierarchy to bridge the gap?

		// if collapse is specified, then find the most direct ancestor of type
		// $groupClassName
		if($collapse) {
			// The most direct ancestors with the type $groupClassName
			$parentSet = array();

			// get direct parents
			$parents = DataObject::get($groupClassName, "\"$groupTable\".\"$parentField\" IN( " . implode( ",", array_keys( $groupedSet ) ) . ")", $sortParents );	
			
			// for each of these parents...
			foreach($parents as $parent) {
				// store the old parent ID. This is required to change the grouped items
				// in the $groupSet array
				$oldParentID = $parent->ID;

				// get the parental stack
				$parentObjects= $parent->parentStack();
				$parentStack = array();

				foreach( $parentObjects as $parentObj )
					$parentStack[] = $parentObj->ID;

				// is some particular IDs are required, then get the intersection
				if($requiredParents && count($requiredParents)) {
					$parentStack = array_intersect($requiredParents, $parentStack);
				}

				$newParent = null;

				// If there are no parents, the group can be omitted
				if(empty($parentStack)) {
					$newParent = new DataObjectSet();
				} else {
				 	$newParent = DataObject::get_one( $groupClassName, "\"$groupTable\".\"$parentField\" IN( " . implode( ",", $parentStack ) . ")" );		
				}
			
				// change each of the descendant's association from the old parent to
				// the new parent. This effectively collapses the hierarchy
				foreach( $groupedSet[$oldParentID] as $descendant ) {
					$groupedSet[$newParent->ID][] = $descendant;
				}

				// Add the most direct ancestor of type $groupClassName
				$parentSet[] = $newParent;
			}
		// otherwise get the parents of these items
		} else {

			$requiredIDs = array_keys( $groupedSet );
			
			if( $requiredParents && cont($requiredParents)) {
				$requiredIDs = array_intersect($requiredParents, $requiredIDs);
			}
				
			if(empty($requiredIDs)) {
				$parentSet = new DataObjectSet();
			} else {
				$parentSet = DataObject::get( $groupClassName, "\"$groupTable\".\"$parentField\" IN( " . implode( ",", $requiredIDs ) . ")", $sortParents );	
			}
			
			$parentSet = $parentSet->toArray();
		}
		
		foreach($parentSet as $parent) {
			$resultsArray[] = $parent->customise(array(
				"GroupItems" => new DataObjectSet($groupedSet[$parent->$parentField])
			));
		}
			
		return new DataObjectSet($resultsArray);
	}
	
	/**
	 * Add a field to this set without writing it to the database
	 * @param DataObject $field Field to add
	 */
    function addWithoutWrite($field) {
        $this->items[] = $field;
	}
	
	/**
	 * Returns true if the DataObjectSet contains all of the IDs givem
	 * @param $idList An array of object IDs
	 */
	function containsIDs($idList) {
		foreach($idList as $item) $wants[$item] = true;
		foreach($this as $item) if($item) unset($wants[$item->ID]);
		return !$wants;
	}
	
	/**
	 * Returns true if the DataObjectSet contains all of and *only* the IDs given.
	 * Note that it won't like duplicates very much.
	 * @param $idList An array of object IDs
	 */
	function onlyContainsIDs($idList) {
		return $this->containsIDs($idList) && sizeof($idList) == $this->count(); 
	}
	
}

/**
 * Sort a 2D array by particular column.
 * @param array $data The array to sort.
 * @param mixed $column The name of the column you wish to sort by, or an array of column=>directions to sort by.
 * @param string $direction Direction to sort by, either "ASC" or "DESC".
 * @param boolean $preserveIndexes Preserve indexes
 */
function column_sort(&$data, $column, $direction = "ASC", $preserveIndexes = true) {
	global $column_sort_field;
	
	// if we were only given a string for column, move it into an array
	if (is_string($column)) $column = array($column => $direction);
	
	// convert directions to integers
	foreach ($column as $k => $v) {
		if ($v == 'ASC') {
			$column[$k] = 1;
		}
		elseif ($v == 'DESC') {
			$column[$k] = -1;
		}
		elseif (!is_numeric($v)) {
			$column[$k] = 0;
		}
	}
	
	$column_sort_field = $column;
	
	if($preserveIndexes) {
		uasort($data, "column_sort_callback_basic");
	} else {
		usort($data, "column_sort_callback_basic");
	}
}

/**
 * Callback used by column_sort
 */
function column_sort_callback_basic($a, $b) {
	global $column_sort_field;
	$result = 0;
	// loop through each sort field
	foreach ($column_sort_field as $field => $multiplier) {
		// if A < B then no further examination is necessary
		if ($a->$field < $b->$field) {
			$result = -1 * $multiplier;
			break;
		}
		// if A > B then no further examination is necessary
		elseif ($a->$field > $b->$field) {
			$result = $multiplier;
			break;
		}
		// A == B means we need to compare the two using the next field
		// if this was the last field, then function returns that objects
		// are equivalent
	}

	return $result;
}

/**
 * An Iterator for a DataObjectSet
 * 
 * @package sapphire
 * @subpackage model
 */
class DataObjectSet_Iterator implements Iterator {
	function __construct($items) {
		$this->items = $items;
		
		$this->current = $this->prepareItem(current($this->items));
	}
	
	/**
	 * Prepare an item taken from the internal array for 
	 * output by this iterator.  Ensures that it is an object.
	 * @param DataObject $item Item to prepare
	 * @return DataObject
	 */
	protected function prepareItem($item) {
		if(is_object($item)) {
			$item->iteratorProperties(key($this->items), sizeof($this->items));
		}
		// This gives some reliablity but it patches over the root cause of the bug...
		// else if(key($this->items) !== null) $item = new ViewableData();
		return $item;
	}
	
	
	/**
	 * Return the current object of the iterator.
	 * @return DataObject
	 */
	public function current() {
		return $this->current;
	}
	
	/**
	 * Return the key of the current object of the iterator.
	 * @return mixed
	 */
	public function key() {
		return key($this->items);
	}
	
	/**
	 * Return the next item in this set.
	 * @return DataObject
	 */
	public function next() {
		$this->current = $this->prepareItem(next($this->items));
		return $this->current;
	}
	
	/**
	 * Rewind the iterator to the beginning of the set.
	 * @return DataObject The first item in the set.
	 */
	public function rewind() {
		$this->current = $this->prepareItem(reset($this->items));
		return $this->current;
	}
	
	/**
	 * Check the iterator is pointing to a valid item in the set.
	 * @return boolean
	 */
	public function valid() {
	 	return $this->current !== false;
	}
	
	/**
	 * Return the next item in this set without progressing the iterator.
	 * @return DataObject
	 */
	public function peekNext() {
		return $this->getOffset(1);
	}
	
	/**
	 * Return the prvious item in this set, without affecting the iterator.
	 * @return DataObject
	 */
	public function peekPrev() {
		return $this->getOffset(-1);
	}
	
	/**
	 * Return the object in this set offset by $offset from the iterator pointer.
	 * @param int $offset The offset.
	 * @return DataObject|boolean DataObject of offset item, or boolean FALSE if not found
	 */
	public function getOffset($offset) {
		$keys = array_keys($this->items);
		foreach($keys as $i => $key) {
			if($key == key($this->items)) break;
		}
		
		if(isset($keys[$i + $offset])) {
			$requiredKey = $keys[$i + $offset];
			return $this->items[$requiredKey];
		}
		
		return false;
	}
}

?>