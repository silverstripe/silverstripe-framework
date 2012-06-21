<?php
/**
 * Dropdown-like field that allows you to select an item from a hierarchical 
 * AJAX-expandable tree.
 * 
 * Creates a field which opens a dropdown (actually a div via javascript 
 * included for you) which contains a tree with the ability to select a singular 
 * item for the value of the field. This field has the ability to store one-to-one 
 * joins related to hierarchy or a hierarchy based filter.
 * 
 * **Note:** your source object must use an implementation of hierarchy for this 
 * field to generate the tree correctly, e.g. {@link Group}, {@link SiteTree} etc.
 * 
 * All operations are carried out through javascript and provides no fallback
 * to non JS.
 * 
 * <b>Usage</b>.
 * 
 * <code>
 * static $has_one = array(
 *   'RightContent' => 'SiteTree'
 * );
 * 
 * function getCMSFields() {
 * ...
 * $treedropdownfield = new TreeDropdownField("RightContentID", "Choose a page to show on the right:", "SiteTree");
 * ..
 * }
 * </code>
 * 
 * This will generate a tree allowing the user to expand and contract subsections 
 * to find the appropriate page to save to the field.
 * 
 * @see TreeMultiselectField for the same implementation allowing multiple selections
 * @see DropdownField for a simple dropdown field.
 * @see CheckboxSetField for multiple selections through checkboxes.
 * @see OptionsetField for single selections via radiobuttons.
 *
 * @package forms
 * @subpackage fields-relational
 */

class TreeDropdownField extends FormField {
	
	public static $url_handlers = array(
		'$Action!/$ID' => '$Action'
	);
	
	public static $allowed_actions = array(
		'tree'
	);
	
	/**
	 * @ignore
	 */
	protected $sourceObject, $keyField, $labelField, $filterCallback, $searchCallback, $baseID = 0;
	/**
	 * @var string default child method in Hierarcy->getChildrenAsUL
	 */
	protected $childrenMethod = 'AllChildrenIncludingDeleted';
	
	/**
	 * Used by field search to leave only the relevant entries
	 */
	protected $searchIds = null, $showSearch, $searchExpanded = array();
	
	/**
	 * CAVEAT: for search to work properly $labelField must be a database field, 
	 * or you need to setSearchFunction.
	 *
	 * @param string $name the field name
	 * @param string $title the field label
	 * @param sourceObject The object-type to list in the tree.  Must be a 
	 *		{@link Hierarchy} subclass.  Alternatively, you can set this to an 
	 *		array of key/value pairs, like a {@link DropdownField} source.  In 
	 *		this case, the field will act like show a flat list of tree items, 
	 *		without any hierarchy. This is most useful in conjunction with 
	 *		{@link TreeMultiselectField}, for presenting a set of checkboxes in 
	 *		a compact view.
	 *
	 * @param string $keyField to field on the source class to save as the 
	 *		field value (default ID).
	 * @param string $labelField the field name to show as the human-readable 
	 *		value on the tree (default Title).
	 * @param bool $showSearch enable the ability to search the tree by 
	 *		entering the text in the input field.
	 */
	public function __construct($name, $title = null, $sourceObject = 'Group', $keyField = 'ID', $labelField = 'Title', $showSearch = false) {
		$this->sourceObject = $sourceObject;
		$this->keyField     = $keyField;
		$this->labelField   = $labelField;
		$this->showSearch	= $showSearch;
		
		parent::__construct($name, $title);
	}
	
	/**
	 * Set the ID of the root node of the tree. This defaults to 0 - i.e. 
	 * displays the whole tree.
	 *
	 * @param int $ID
	 */
	public function setTreeBaseID($ID) {
		$this->baseID = (int) $ID;
		return $this;
	}
	
	/**
	 * Set a callback used to filter the values of the tree before 
	 * displaying to the user.
	 *
	 * @param callback $callback
	 */
	public function setFilterFunction($callback) {
		if(!is_callable($callback, true)) {
			throw new InvalidArgumentException('TreeDropdownField->setFilterCallback(): not passed a valid callback');
		}
		
		$this->filterCallback = $callback;
		return $this;
	}
	
	/**
	 * Set a callback used to search the hierarchy globally, even before 
	 * applying the filter.
	 *
	 * @param callback $callback
	 */
	public function setSearchFunction($callback) {
		if(!is_callable($callback, true)) {
			throw new InvalidArgumentException('TreeDropdownField->setSearchFunction(): not passed a valid callback');
		}
		
		$this->searchCallback = $callback;
		return $this;
	}

	public function getShowSearch() {
		return $this->showSearch;
	}

	/**
	 * @param Boolean
	 */
	public function setShowSearch($bool) {
		$this->showSearch = $bool;
		return $this;
	}

	/**
	 * @param $method The parameter to ChildrenMethod to use when calling Hierarchy->getChildrenAsUL in {@link Hierarchy}.
	 * The method specified determined the structure of the returned list. Use "ChildFolders" in place of the default
	 * to get a drop-down listing with only folders, i.e. not including the child elements  in the currently selected folder.
	 * See {@link Hierarchy} for a complete list of possible methods.
	 */
	public function setChildrenMethod($method) {
		$this->childrenMethod = $method;
	}

	/**
	 * @return string
	 */
	public function Field($properties = array()) {
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jstree/jquery.jstree.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/TreeDropdownField.js');
		
		Requirements::css(FRAMEWORK_DIR . '/thirdparty/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::css(FRAMEWORK_DIR . '/css/TreeDropdownField.css');
	
		$record = $this->Value() ? $this->objectForKey($this->Value()) : null;
		if($record) {
			$title = $record->{$this->labelField};
		} else {
			$title = _t('DropdownField.CHOOSE', '(Choose)', 'start value of a dropdown');
		}

		// TODO Implement for TreeMultiSelectField
		$metadata = array(
			'id' => $record ? $record->ID : null, 
			'ClassName' => $record ? $record->ClassName : $this->sourceObject
		);

		$properties = array_merge(
			$properties,
			array(
				'Title' => $title,
				'TitleURLEncoded' => rawurlencode($title),
				'Metadata' => ($metadata) ? Convert::raw2att(Convert::raw2json($metadata)) : null
			)
		);

		return $this->customise($properties)->renderWith('TreeDropdownField');
	}

	function extraClass() {
		return implode(' ', array(parent::extraClass(), ($this->showSearch ? "searchable" : null)));
	}
	
	/**
	 * Get the whole tree of a part of the tree via an AJAX request.
	 *
	 * @param SS_HTTPRequest $request
	 * @return string
	 */
	public function tree(SS_HTTPRequest $request) {
		// Array sourceObject is an explicit list of values - construct a "flat tree"
		if(is_array($this->sourceObject)) {
			$output = "<ul class=\"tree\">\n";
			foreach($this->sourceObject as $k => $v) {
				$output .= '<li id="selector-' . $this->name . '-' . $k  . '"><a>' . $v . '</a>';
			}
			$output .= "</ul>";
			return $output;
		}
		
		// Regular source specification
		$isSubTree = false;

		$this->search = Convert::Raw2SQL($request->requestVar('search'));
		$ID = (is_numeric($request->latestparam('ID'))) ? (int)$request->latestparam('ID') : (int)$request->requestVar('ID');
		$forceFullTree = $request->requestVar('forceFullTree')?$request->requestVar('forceFullTree'):false;
		if($ID && !$forceFullTree) {
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
		
		// allow to pass values to be selected within the ajax request
		if( isset($_REQUEST['forceValue']) || $this->value ) {
			$forceValue = ( isset($_REQUEST['forceValue']) ? $_REQUEST['forceValue'] : $this->value);
			if(($values = preg_split('/,\s*/', $forceValue)) && count($values)) foreach($values as $value) {
				if(!$value) continue;
				
				$obj->markToExpose($this->objectForKey($value));
			}
		}
		$eval = '"<li id=\"selector-' . $this->getName() . '-{$child->' . $this->keyField . '}\" data-id=\"$child->' . $this->keyField . '\" class=\"class-$child->class"' .
				' . $child->markingClasses() . "\"><a rel=\"$child->ID\">" . $child->' . $this->labelField . ' . "</a>"';

		if($isSubTree) {
			return substr(trim($obj->getChildrenAsUL('', $eval, null, true, $this->childrenMethod)), 4, -5);
		} else {
			return $obj->getChildrenAsUL('class="tree"', $eval, null, true, $this->childrenMethod);
		}
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
			$res = DataObject::get($this->sourceObject, "\"$this->labelField\" LIKE '%$this->search%'");
		
		if( $res ) {
			// iteratively fetch the parents in bulk, until all the leaves can be accessed using the tree control
			foreach($res as $row) {
				if ($row->ParentID) $parents[$row->ParentID] = true;
				$this->searchIds[$row->ID] = true;
			}
			while (!empty($parents)) {
				$res = DB::query('SELECT "ParentID", "ID" FROM "' . $this->sourceObject . '" WHERE "ID" in ('.implode(',',array_keys($parents)).')');
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

/**
 * @package forms
 * @subpackage fields-relational
 */
class TreeDropdownField_Readonly extends TreeDropdownField {
	protected $readonly = true;
	
	function Field($properties = array()) {
		$fieldName = $this->labelField;
		if($this->value) {
			$keyObj = $this->objectForKey($this->value);
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
