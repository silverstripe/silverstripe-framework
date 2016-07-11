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

	private static $url_handlers = array(
		'$Action!/$ID' => '$Action'
	);

	private static $allowed_actions = array(
		'tree'
	);

	/**
	 * @ignore
	 */
	protected $sourceObject, $keyField, $labelField, $filterCallback,
		$disableCallback, $searchCallback, $baseID = 0;
	/**
	 * @var string default child method in Hierarchy->getChildrenAsUL
	 */
	protected $childrenMethod = 'AllChildrenIncludingDeleted';

	/**
	 * @var string default child counting method in Hierarchy->getChildrenAsUL
	 */
	protected $numChildrenMethod = 'numChildren';

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
	 * @param string|array $sourceObject The object-type to list in the tree. This could
	 * be one of the following:
	 * - A DataObject class name with the {@link Hierarchy} extension.
	 * - An array of key/value pairs, like a {@link DropdownField} source. In
	 *   this case, the field will act like show a flat list of tree items,
	 *	 without any hierarchy. This is most useful in conjunction with
	 *   {@link TreeMultiselectField}, for presenting a set of checkboxes in
	 *   a compact view. Note, that all value strings must be XML encoded
	 *   safely prior to being passed in.
	 *
	 * @param string $keyField to field on the source class to save as the
	 *		field value (default ID).
	 * @param string $labelField the field name to show as the human-readable
	 *		value on the tree (default Title).
	 * @param bool $showSearch enable the ability to search the tree by
	 *		entering the text in the input field.
	 */
	public function __construct($name, $title = null, $sourceObject = 'Group', $keyField = 'ID',
		$labelField = 'TreeTitle', $showSearch = true
	) {

		$this->sourceObject = $sourceObject;
		$this->keyField     = $keyField;
		$this->labelField   = $labelField;
		$this->showSearch	= $showSearch;

		//Extra settings for Folders
		if ($sourceObject == 'Folder') {
			$this->childrenMethod = 'ChildFolders';
			$this->numChildrenMethod = 'numChildFolders';
		}

		$this->addExtraClass('single');

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
	 * Set a callback used to disable checkboxes for some items in the tree
	 *
	 * @param callback $callback
	 */
	public function setDisableFunction($callback) {
		if(!is_callable($callback, true)) {
			throw new InvalidArgumentException('TreeDropdownField->setDisableFunction(): not passed a valid callback');
		}

		$this->disableCallback = $callback;
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
	 * @param $method The parameter to ChildrenMethod to use when calling Hierarchy->getChildrenAsUL in
	 * {@link Hierarchy}. The method specified determines the structure of the returned list. Use "ChildFolders"
	 * in place of the default to get a drop-down listing with only folders, i.e. not including the child elements in
	 * the currently selected folder. setNumChildrenMethod() should be used as well for proper functioning.
	 *
	 * See {@link Hierarchy} for a complete list of possible methods.
	 */
	public function setChildrenMethod($method) {
		$this->childrenMethod = $method;
		return $this;
	}

	/**
	 * @param $method The parameter to numChildrenMethod to use when calling Hierarchy->getChildrenAsUL in
	 * {@link Hierarchy}. Should be used in conjunction with setChildrenMethod().
	 *
	 */
	public function setNumChildrenMethod($method) {
		$this->numChildrenMethod = $method;
		return $this;
	}

	/**
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');

		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jstree/jquery.jstree.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/TreeDropdownField.js');

		Requirements::css(FRAMEWORK_DIR . '/thirdparty/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::css(FRAMEWORK_DIR . '/css/TreeDropdownField.css');

		if($this->showSearch) {
			$emptyTitle = _t('DropdownField.CHOOSESEARCH', '(Choose or Search)', 'start value of a dropdown');
		} else {
			$emptyTitle = _t('DropdownField.CHOOSE', '(Choose)', 'start value of a dropdown');
		}

		$record = $this->Value() ? $this->objectForKey($this->Value()) : null;
		if($record instanceof ViewableData) {
			$title = $record->obj($this->labelField)->forTemplate();
		} elseif($record) {
			$title = Convert::raw2xml($record->{$this->labelField});
		}
		else {
			$title = $emptyTitle;
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
				'EmptyTitle' => $emptyTitle,
				'Metadata' => ($metadata) ? Convert::raw2json($metadata) : null,
			)
		);

		return parent::Field($properties);
	}

	public function extraClass() {
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

		$this->search = $request->requestVar('search');
		$ID = (is_numeric($request->latestparam('ID')))
			? (int)$request->latestparam('ID')
			: (int)$request->requestVar('ID');

		if($ID && !$request->requestVar('forceFullTree')) {
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

		if ($this->filterCallback || $this->search != "" )
			$obj->setMarkingFilterFunction(array($this, "filterMarking"));

		$obj->markPartialTree($nodeCountThreshold = 30, $context = null,
			$this->childrenMethod, $this->numChildrenMethod);

		// allow to pass values to be selected within the ajax request
		if( isset($_REQUEST['forceValue']) || $this->value ) {
			$forceValue = ( isset($_REQUEST['forceValue']) ? $_REQUEST['forceValue'] : $this->value);
			if(($values = preg_split('/,\s*/', $forceValue)) && count($values)) foreach($values as $value) {
				if(!$value || $value == 'unchanged') continue;

				$obj->markToExpose($this->objectForKey($value));
			}
		}

		$self = $this;
		$titleFn = function(&$child) use(&$self) {
			$keyField = $self->keyField;
			$labelField = $self->labelField;
			return sprintf(
				'<li id="selector-%s-%s" data-id="%s" class="class-%s %s %s"><a rel="%d">%s</a>',
				Convert::raw2xml($self->getName()),
				Convert::raw2xml($child->$keyField),
				Convert::raw2xml($child->$keyField),
				Convert::raw2xml($child->class),
				Convert::raw2xml($child->markingClasses($self->numChildrenMethod)),
				($self->nodeIsDisabled($child)) ? 'disabled' : '',
				(int)$child->ID,
				$child->obj($labelField)->forTemplate()
			);
		};

		// Limit the amount of nodes shown for performance reasons.
		// Skip the check if we're filtering the tree, since its not clear how many children will
		// match the filter criteria until they're queried (and matched up with previously marked nodes).
		$nodeThresholdLeaf = Config::inst()->get('Hierarchy', 'node_threshold_leaf');
		if($nodeThresholdLeaf && !$this->filterCallback && !$this->search) {
			$className = $this->sourceObject;
			$nodeCountCallback = function($parent, $numChildren) use($className, $nodeThresholdLeaf) {
				if($className == 'SiteTree' && $parent->ID && $numChildren > $nodeThresholdLeaf) {
					return sprintf(
						'<ul><li><span class="item">%s</span></li></ul>',
						_t('LeftAndMain.TooManyPages', 'Too many pages')
					);
				}
			};
		} else {
			$nodeCountCallback = null;
		}

		if($isSubTree) {
			$html = $obj->getChildrenAsUL(
				"",
				$titleFn,
				null,
				true,
				$this->childrenMethod,
				$this->numChildrenMethod,
				true, // root call
				null,
				$nodeCountCallback
			);
			return substr(trim($html), 4, -5);
		} else {
			$html = $obj->getChildrenAsUL(
				'class="tree"',
				$titleFn,
				null,
				true,
				$this->childrenMethod,
				$this->numChildrenMethod,
				true, // root call
				null,
				$nodeCountCallback
			);
			return $html;
		}
	}

	/**
	 * Marking public function for the tree, which combines different filters sensibly.
	 * If a filter function has been set, that will be called. And if search text is set,
	 * filter on that too. Return true if all applicable conditions are true, false otherwise.
	 * @param $node
	 * @return unknown_type
	 */
	public function filterMarking($node) {
		if ($this->filterCallback && !call_user_func($this->filterCallback, $node)) return false;
		if ($this->search != "") {
			return isset($this->searchIds[$node->ID]) && $this->searchIds[$node->ID] ? true : false;
		}

		return true;
	}

	/**
	 * Marking a specific node in the tree as disabled
	 * @param $node
	 * @return boolean
	 */
	public function nodeIsDisabled($node) {
		return ($this->disableCallback && call_user_func($this->disableCallback, $node));
	}

	/**
	 * @param String $field
	 */
	public function setLabelField($field) {
		$this->labelField = $field;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getLabelField() {
		return $this->labelField;
	}

	/**
	 * @param String $field
	 */
	public function setKeyField($field) {
		$this->keyField = $field;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getKeyField() {
		return $this->keyField;
	}

	/**
	 * @param String $field
	 */
	public function setSourceObject($class) {
		$this->sourceObject = $class;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getSourceObject() {
		return $this->sourceObject;
	}

	/**
	 * Populate $this->searchIds with the IDs of the pages matching the searched parameter and their parents.
	 * Reverse-constructs the tree starting from the leaves. Initially taken from CMSSiteTreeFilter, but modified
	 * with pluggable search function.
	 */
	protected function populateIDs() {
		// get all the leaves to be displayed
		if ($this->searchCallback) {
			$res = call_user_func($this->searchCallback, $this->sourceObject, $this->labelField, $this->search);
		} else {
			$sourceObject = $this->sourceObject;
			$filters = array();
			if(singleton($sourceObject)->hasDatabaseField($this->labelField)) {
				$filters["{$this->labelField}:PartialMatch"]  = $this->search;
			} else {
				if(singleton($sourceObject)->hasDatabaseField('Title')) {
					$filters["Title:PartialMatch"] = $this->search;
				}
				if(singleton($sourceObject)->hasDatabaseField('Name')) {
					$filters["Name:PartialMatch"] = $this->search;
				}
			}

			if(empty($filters)) {
				throw new InvalidArgumentException(sprintf(
					'Cannot query by %s.%s, not a valid database column',
					$sourceObject,
					$this->labelField
				));
			}
			$res = DataObject::get($this->sourceObject)->filterAny($filters);
		}

		if( $res ) {
			// iteratively fetch the parents in bulk, until all the leaves can be accessed using the tree control
			foreach($res as $row) {
				if ($row->ParentID) $parents[$row->ParentID] = true;
				$this->searchIds[$row->ID] = true;
			}

			$sourceObject = $this->sourceObject;

			while (!empty($parents)) {
				$items = $sourceObject::get()
					->filter("ID",array_keys($parents));
				$parents = array();

				foreach($items as $item) {
					if ($item->ParentID) $parents[$item->ParentID] = true;
					$this->searchIds[$item->ID] = true;
					$this->searchExpanded[$item->ID] = true;
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
		return DataObject::get($this->sourceObject)
			->filter($this->keyField, $key)
			->first();
	}

	/**
	 * Changes this field to the readonly field.
	 */
	public function performReadonlyTransformation() {
		$copy = $this->castedCopy('TreeDropdownField_Readonly');
		$copy->setKeyField($this->keyField);
		$copy->setLabelField($this->labelField);
		$copy->setSourceObject($this->sourceObject);

		return $copy;
	}

}

/**
 * @package forms
 * @subpackage fields-relational
 */
class TreeDropdownField_Readonly extends TreeDropdownField {
	protected $readonly = true;

	public function Field($properties = array()) {
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
		$field->dontEscape = true;
		return $field->Field();
	}
}
