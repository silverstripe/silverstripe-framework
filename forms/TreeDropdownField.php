<?php
/**
 * Dropdown-like field that allows you to select an item from a hierachical AJAX-expandable tree
 *
 * @package forms
 * @subpackage fields-relational
 */
class TreeDropdownField extends FormField {
	
	public static $url_handlers = array (
		'$Action!/$ID' => '$Action'
	);
	
	public static $allowed_actions = array (
		'tree'
	);
	
	/**
	 * @ignore
	 */
	protected $sourceObject, $keyField, $labelField, $filterCallback, $searchCallback, $baseID = 0;
	
	/**
	 * Used by field search to leave only the relevant entries
	 */
	protected $searchIds = null, $searchExpanded = array();
	
	/**
	 * CAVEAT: for search to work properly $labelField must be a database field, or you need to setSearchFunction.
	 *
	 * @param string $name the field name
	 * @param string $title the field label
	 * @param string $souceClass the class to display in the tree, must have the "Hierachy" extension.
	 * @param string $keyField to field on the source class to save as the field value (default ID).
	 * @param string $labelField the field name to show as the human-readable value on the tree (default Title).
	 * @param string $showSearch enable the ability to search the tree by entering the text in the input field.
	 */
	public function __construct($name, $title = null, $sourceObject = 'Group', $keyField = 'ID', $labelField = 'Title', $showSearch = false) {
		$this->sourceObject = $sourceObject;
		$this->keyField     = $keyField;
		$this->labelField   = $labelField;
		$this->showSearch	= $showSearch;
		
		if(!Object::has_extension($this->sourceObject, 'Hierarchy')) {
			throw new Exception (
				"TreeDropdownField: the source class '$this->sourceObject' must have the Hierarchy extension applied"
			);
		}
		
		parent::__construct($name, $title);
	}
	
	/**
	 * Set the ID of the root node of the tree. This defaults to 0 - i.e. displays the whole tree.
	 *
	 * @param int $ID
	 */
	public function setTreeBaseID($ID) {
		$this->baseID = (int) $ID;
	}
	
	/**
	 * Set a callback used to filter the values of the tree before displaying to the user.
	 *
	 * @param callback $callback
	 */
	public function setFilterFunction($callback) {
		if(!is_callable($callback, true)) {
			throw new InvalidArgumentException('TreeDropdownField->setFilterCallback(): not passed a valid callback');
		}
		
		$this->filterCallback = $callback;
	}
	
	/**
	 * Set a callback used to search the hierarchy globally, even before applying the filter.
	 *
	 * @param callback $callback
	 */
	public function setSearchFunction($callback) {
		if(!is_callable($callback, true)) {
			throw new InvalidArgumentException('TreeDropdownField->setSearchFunction(): not passed a valid callback');
		}
		
		$this->searchCallback = $callback;
	}
	
	/**
	 * @return string
	 */
	public function Field() {
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/tree/tree.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/TreeSelectorField.js');
		
		Requirements::css(SAPPHIRE_DIR . '/javascript/tree/tree.css');
		Requirements::css(SAPPHIRE_DIR . '/css/TreeDropdownField.css');
	
		if($this->Value() && $record = $this->objectForKey($this->Value())) {
			$title = $record->{$this->labelField};
		} else {
			$title = _t('DropdownField.CHOOSE', '(Choose)', PR_MEDIUM, 'start value of a dropdown');
		}
		
		return $this->createTag (
			'div',
			array (
				'id'    => "TreeDropdownField_{$this->id()}",
				'class' => 'TreeDropdownField single' . ($this->extraClass() ? " {$this->extraClass()}" : '')
			),
			$this->createTag (
				'input',
				array (
					'id'    => $this->id(),
					'type'  => 'hidden',
					'name'  => $this->name,
					'value' => $this->value
				)
			) . ($this->showSearch ?
					$this->createTag(
						'input',
						array(
							'class' => 'items',
							'value' => '(Choose or type search)' 
						)
					) :
					$this->createTag (
						'span',
						array (
							'class' => 'items'
						),
						$title
					)					
			) . $this->createTag (
				'a',
				array (
					'href'  => '#',
					'title' => 'open',
					'class' => 'editLink'
				),
				'&nbsp;'
			) 
		);
	}
	
	/**
	 * Get the whole tree of a part of the tree via an AJAX request.
	 *
	 * @param SS_HTTPRequest $request
	 * @return string
	 */
	public function tree(SS_HTTPRequest $request) {
		$isSubTree = false;

		$this->search = Convert::Raw2SQL($request->getVar('search'));

		if($ID = (int) $request->latestparam('ID')) {
			$obj       = DataObject::get_by_id($this->sourceObject, $ID);
			$isSubTree = true;
			
			if(!$obj) {
				throw new Exception (
					"TreeDropdownField->tree(): the object #$ID of type $this->sourceObject could not be found"
				);
			}
		} else {
			if($this->baseID) {
				$obj = DataObject::get_by_id($this->sourceObject, $this->baseID);
			}
			
			if(!$this->baseID || !$obj) $obj = singleton($this->sourceObject);
		}

		// pre-process the tree - search needs to operate globally, not locally as marking filter does
		if ( $this->search != "" )
			$this->populateIDs();
		
		if ($this->filterCallback || $this->sourceObject == 'Folder' || $this->search != "" )
			$obj->setMarkingFilterFunction(array($this, "filterMarking"));
		
		$obj->markPartialTree();
		
		if($forceValues = $this->value) {
			if(($values = preg_split('/,\s*/', $forceValues)) && count($values)) foreach($values as $value) {
				$obj->markToExpose($this->objectForKey($value));
			}
		}
		
		$eval = '"<li id=\"selector-' . $this->Name() . '-{$child->' . $this->keyField . '}\" class=\"$child->class"' .
				' . $child->markingClasses() . "\"><a rel=\"$child->ID\">" . $child->' . $this->labelField . ' . "</a>"';
		
		if($isSubTree) {
			return substr(trim($obj->getChildrenAsUL('', $eval, null, true)), 4, -5);
		}
		
		return $obj->getChildrenAsUL('class="tree"', $eval, null, true);
	}

	/**
	 * Marking function for the tree, which combines different filters sensibly. If a filter function has been set,
	 * that will be called. If the source is a folder, automatically filter folder. And if search text is set, filter on that
	 * too. Return true if all applicable conditions are true, false otherwise.
	 * @param $node
	 * @return unknown_type
	 */
	function filterMarking($node) {
		if ($this->filterCallback && !call_user_func($this->filterCallback, $node)) return false;
		if ($this->sourceObject == "Folder" && $node->ClassName != 'Folder') return false;
		if ($this->search != "") {
			return isset($this->searchIds[$node->ID]) && $this->searchIds[$node->ID] ? true : false;
		}
		
		return true;
	}
	
	/**
	 * Populate $this->searchIds with the IDs of the pages matching the searched parameter and their parents.
	 * Reverse-constructs the tree starting from the leaves. Initially taken from CMSSiteTreeFilter, but modified
	 * with pluggable search function.
	 */
	protected function populateIDs() {
		// get all the leaves to be displayed
		if ( $this->searchCallback )
			$res = call_user_func($this->searchCallback, $this->sourceObject, $this->labelField, $this->search);
		else
			$res = DataObject::get($this->sourceObject, "$this->labelField LIKE '%$this->search%'");
		
		if( $res ) {
			// iteratively fetch the parents in bulk, until all the leaves can be accessed using the tree control
			foreach($res as $row) {
				if ($row->ParentID) $parents[$row->ParentID] = true;
				$this->searchIds[$row->ID] = true;
			}
			while (!empty($parents)) {
				$res = DB::query('SELECT "ParentID", "ID" FROM '.$this->sourceObject.' WHERE "ID" in ('.implode(',',array_keys($parents)).')');
				$parents = array();

				foreach($res as $row) {
					if ($row['ParentID']) $parents[$row['ParentID']] = true;
					$this->searchIds[$row['ID']] = true;
					$this->searchExpanded[$row['ID']] = true;
				}
			}
		}
	}

	/**
	 * Get the object where the $keyField is equal to a certain value
	 *
	 * @param string|int $key
	 * @return DataObject
	 */
	protected function objectForKey($key) {
		if($this->keyField == 'ID') {
			return DataObject::get_by_id($this->sourceObject, $key);
		} else {
			return DataObject::get_one($this->sourceObject, "\"{$this->keyField}\" = '" . Convert::raw2sql($key) . "'");
		}
	}

	/**
	 * Changes this field to the readonly field.
	 */
	function performReadonlyTransformation() {
		return new TreeDropdownField_Readonly($this->name, $this->title, $this->sourceObject, $this->keyField, $this->labelField);
	}
}

class TreeDropdownField_Readonly extends TreeDropdownField {
	protected $readonly = true;
	
	function Field() {
		$fieldName = $this->labelField;
		if($this->value) {
			$keyObj = $this->getByKey($this->value);
			$obj = $keyObj ? $keyObj->$fieldName : '';
		} else {
			$obj = null;
		}

		$source = array(
			$this->value => $obj
		);

		$field = new LookupField($this->name, $this->title, $source);
		$field->setValue($this->value);
		$field->setForm($this->form);
		return $field->Field();
	}
}
