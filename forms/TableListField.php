<?php

/**
 * @package forms
 * @subpackage fields-relational
 */

/**
 * Form field that embeds a list into a form, such as a member list or a file list.
 * 
 * All get variables are namespaced in the format ctf[MyFieldName][MyParameter] to avoid collisions
 * when multiple TableListFields are present in a form.
 * 
 * @deprecated 3.0 Use GridField with GridFieldConfig_RecordEditor
 * 
 * @package forms
 * @subpackage fields-relational
 */
class TableListField extends FormField {
	/**
	 * The {@link DataList} object defining the source data for this view/
	 */
	protected $dataList;
	
	protected $fieldList;
	
	protected $disableSorting = false;
	
	/**
	 * @var $fieldListCsv array
	 */
	protected $fieldListCsv;
	
	/**
	 * @var $clickAction
	 */
	protected $clickAction;
	
	/**
	 * @var bool
	 */
	public $IsReadOnly;
	
	/**
	 * Called method (needs to be retained for AddMode())
	 */
	protected $methodName;
	
	/**
	 * @var $summaryFieldList array Shows a row which summarizes the contents of a column by a predefined
	 * Javascript-function 
	 */
	protected $summaryFieldList;
	
	/**
	 * @var $summaryTitle string The title which will be shown in the first column of the summary-row.
	 * Accordingly, the first column can't be used for summarizing.
	 */
	protected $summaryTitle;
	
	/**
	 * @var $template string Template-Overrides
	 */
	protected $template = "TableListField";
	
	/**
	 * @var $itemClass string Class name for each item/row
	 */
	public $itemClass = 'TableListField_Item';
	
	/**
	 * @var bool Do we use checkboxes to mark records, or delete them one by one?
	 */
	public $Markable;
	
	public $MarkableTitle = null;
	
	/**
	 * @var array See {@link SelectOptions()}
	 */
	
	protected $selectOptions = array();
	
	/**
	 * @var $readOnly boolean Deprecated, please use $permssions instead
	 */
	protected $readOnly;
	
	/**
	 * @var $permissions array Influence output without having to subclass the template.
	 * See $actions for adding your custom actions/permissions.
	 */
	protected $permissions = array(
		//"print",
		//"export",
		"delete"
	);
	
	/**
	 * @var $actions array Action that can be performed on a single row-entry.
	 * Has to correspond to a method in a TableListField-class (or subclass).
	 * Actions can be disabled through $permissions.
	 * Format (key is used for the methodname and CSS-class): 
	 * array(
	 * 	'delete' => array(
	 * 		'label' => 'Delete', 
	 * 		'icon' => 'sapphire/images/delete.gif',
	 * 		'icon_disabled' => 'sapphire/images/delete_disabled.gif',
	 * 		'class' => 'deletelink',
	 * 	)
	 * )
	 */
	public $actions = array(
		'delete' => array(
			'label' => 'Delete',
			'icon' => 'sapphire/images/delete.gif',
			'icon_disabled' => 'sapphire/images/delete_disabled.gif',
			'class' => 'deletelink' 
		)
	);
	
	/**
	 * @var $defaultAction String Action being executed when clicking on table-row (defaults to "show").
	 * Mostly needed in ComplexTableField-subclass.
	 */
	public $defaultAction = '';

	/**
	 * @var $customCsvQuery Query for CSV-export (might need different fields or further filtering)
	 */
	protected $customCsvQuery;
	
	/**
	 * Character to seperate exported columns in the CSV file
	 */
	protected $csvSeparator = ",";
	
	/*
	 * Boolean deciding whether to include a header row in the CSV file
	 */
	protected $csvHasHeader = true;
	
	/**
	 * @var array Specify custom escape for the fields.
	 *
	 * <code>
	 * array("\""=>"\"\"","\r"=>"", "\r\n"=>"", "\n"=>"")
	 * </code>
	 */
	public $csvFieldEscape = array(
		"\""=>"\"\"",
		"\r\n"=>"", 
		"\r"=>"",
		"\n"=>"",
	);
	
	/**
	 * @var boolean Trigger pagination
	 */
	protected $showPagination = false;
	
	/**
	 * @var string Override the {@link Link()} method
	 * for all pagination. Useful to force rendering of the field
	 * in a different context.
	 */
	public $paginationBaseLink = null;
	
	/**
	 * @var int Number of items to show on a single page (needed for pagination)
	 */
	protected $pageSize = 10;
	
	/**
	 * @var array Definitions for highlighting table-rows with a specific class. You can use all column-names
	 * in the result of a query. Use in combination with {@setCustomQuery} to select custom properties and joined objects.
	 *  
	 * Example:
	 * array(
	 * 	array(
	 * 		"rule" => '$Flag == "red"',
	 *	 	"class" => "red"
	 * 	),
	 * 	array(
	 * 		"rule" => '$Flag == "orange"',
	 * 		"class" => "orange"
	 * 	)
	 * )
	 */
	public $highlightConditions = array();
	
	/**
	 * @var array Specify castings with fieldname as the key, and the desired casting as value.
	 * Example: array("MyCustomDate"=>"Date","MyShortText"=>"Text->FirstSentence")
	 */
	public $fieldCasting = array();
	
	/**
	 * @var array Specify custom formatting for fields, e.g. to render a link instead of pure text.
	 * Caution: Make sure to escape special php-characters like in a normal php-statement. 
	 * Example:	"myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'
	 */
	public $fieldFormatting = array();
	
	public $csvFieldFormatting = array();
	
	/**
	 * @var string
	 */
	public $exportButtonLabel = 'Export as CSV';
	
	/**
	 * @var string $groupByField Used to group by a specific column in the DataObject
	 * and create partial summaries.
	 */
	public $groupByField = null;
	
	/**
	 * @var array
	 */
	protected $extraLinkParams;
	
	protected $__cachedQuery;
	
	/**
	 * This is a flag that enables some backward-compatibility helpers.
	 */
	private $getDataListFromForm;
	
	/**
	 * @param $name string The fieldname
	 * @param $sourceClass string The source class of this field
	 * @param $fieldList array An array of field headings of Fieldname => Heading Text (eg. heading1)
	 * @param $sourceFilter string The filter field you wish to limit the objects by (eg. parentID)
	 * @param $sourceSort string
	 * @param $sourceJoin string
	 */
	function __construct($name, $sourceClass = null, $fieldList = null, $sourceFilter = null, 
		$sourceSort = null, $sourceJoin = null) {
		if(FRAMEWORK_DIR != 'sapphire' && !SapphireTest::is_running_test()) {
			user_error('TableListField requires FRAMEWORK_DIR to be sapphire.', E_USER_WARNING);
		}

		if($sourceClass) {
			// You can optionally pass a list
			if($sourceClass instanceof SS_List) {
				$this->dataList = $sourceClass;
				
			} else {
				$this->dataList = DataObject::get($sourceClass)->where($sourceFilter)->sort($sourceSort);
				if($sourceJoin) $this->dataList = $this->dataList->join($sourceJoin);
				// Grab it from the form relation, if available.
				$this->getDataListFromForm = true;
			}
		}

		$this->fieldList = ($fieldList) ? $fieldList : singleton($this->sourceClass())->summaryFields();

		$this->readOnly = false;

		parent::__construct($name);
	}
	
	function index() {
		return $this->FieldHolder();
	}

	static $url_handlers = array(
		'item/$ID' => 'handleItem',
		'$Action' => '$Action',
	);

	function sourceClass() {
	    $list = $this->getDataList();
	    if(method_exists($list, 'dataClass')) return $list->dataClass();
	    // Failover for SS_List
	    else return get_class($list->First());
	}
	
	function handleItem($request) {
		return new TableListField_ItemRequest($this, $request->param('ID'));
	}
	
	function FieldHolder($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/TableListField.js');
		Requirements::css(FRAMEWORK_DIR . '/css/TableListField.css');
		
		if($this->clickAction) {
			$id = $this->id();
			Requirements::customScript(<<<JS
				Behaviour.register({
					'#$id tr' : {
						onclick : function() {
							$this->clickAction
							return false;
						}
				}
			});
JS
		);}

		$obj = $properties ? $this->customise($properties) : $this;
		return $obj->renderWith($this->template);
	}
	
	function Headings() {
		$headings = array();
		foreach($this->fieldList as $fieldName => $fieldTitle) {
			$isSorted = (isset($_REQUEST['ctf'][$this->getName()]['sort']) && $fieldName == $_REQUEST['ctf'][$this->getName()]['sort']);
			// we can't allow sorting with partial summaries (groupByField)
			$isSortable = ($this->form && $this->isFieldSortable($fieldName) && !$this->groupByField);

			// sorting links (only if we have a form to refresh with)
			if($this->form) {
				$sortLink = $this->Link();
				$sortLink = HTTP::setGetVar("ctf[{$this->getName()}][sort]", $fieldName, $sortLink,'&');
	
				// Apply sort direction to the current sort field
				if(!empty($_REQUEST['ctf'][$this->getName()]['sort']) && ($_REQUEST['ctf'][$this->getName()]['sort'] == $fieldName)) {
					$dir = isset($_REQUEST['ctf'][$this->getName()]['dir']) ? $_REQUEST['ctf'][$this->getName()]['dir'] : null;
					$dir = trim(strtolower($dir));
					$newDir = ($dir == 'desc') ? null : 'desc';
					$sortLink = HTTP::setGetVar("ctf[{$this->getName()}][dir]", Convert::raw2xml($newDir), $sortLink,'&');
				}

				if(isset($_REQUEST['ctf'][$this->getName()]['search']) && is_array($_REQUEST['ctf'][$this->getName()]['search'])) {
					foreach($_REQUEST['ctf'][$this->getName()]['search'] as $parameter => $value) {
						$XML_search = Convert::raw2xml($value);
						$sortLink = HTTP::setGetVar("ctf[{$this->getName()}][search][$parameter]", $XML_search, $sortLink,'&');
					}
				}
			} else {
				$sortLink = '#';
			}
			
			$headings[] = new ArrayData(array(
				"Name" => $fieldName, 
				"Title" => ($this->sourceClass()) ? singleton($this->sourceClass())->fieldLabel($fieldTitle) : $fieldTitle,
				"IsSortable" => $isSortable,
				"SortLink" => $sortLink,
				"SortBy" => $isSorted,
				"SortDirection" => (isset($_REQUEST['ctf'][$this->getName()]['dir'])) ? $_REQUEST['ctf'][$this->getName()]['dir'] : null 
			));
		}
		return new ArrayList($headings);
	}
	
	function disableSorting($to = true) {
		$this->disableSorting = $to;
	}

	/**
	 * Determines if a field is "sortable".
	 * If the field is generated by a custom getter, we can't sort on it
	 * without generating all objects first (which would be a huge performance impact).
	 * 
	 * @param string $fieldName
	 * @return bool
	 */	
	function isFieldSortable($fieldName) {
		if($this->disableSorting) return false;
		$list = $this->getDataList();
		if(method_exists($list,'canSortBy')) return $list->canSortBy($fieldName);
		else return false;
	}
	
	/**
	 * Dummy function to get number of actions originally generated in
	 * TableListField_Item.
	 * 
	 * @return SS_List
	 */
	function Actions() {
		$allowedActions = new ArrayList();
		foreach($this->actions as $actionName => $actionSettings) {
			if($this->Can($actionName)) {
				$allowedActions->push(new ViewableData());
			}
		}

		return $allowedActions;
	}
	
	/**
	 * Provide a custom query to compute sourceItems. This is the preferred way to using
	 * {@setSourceItems}, because we can still paginate.
	 * Please use this only as a fallback for really complex queries (e.g. involving HAVING and GROUPBY).  
	 * 
	 * @param $query DataList
	 */
	function setCustomQuery(DataList $dataList) {
		$this->dataList = $dataList;
		return $this;
	}

	function setCustomCsvQuery(DataList $dataList) {
		$this->customCsvQuery = $query;
		return $this;
	}
	
	function setCustomSourceItems(SS_List $items) {
		user_error('TableList::setCustomSourceItems() deprecated, just pass the items into the constructor', E_USER_WARNING);

		// The type-hinting above doesn't seem to work consistently
		if($items instanceof SS_List) {
		    $this->dataList = $items;
		} else {
			user_error('TableList::setCustomSourceItems() should be passed a SS_List', E_USER_WARNING);
		}

		return $this;
	}
	
	/**
	 * Get items, with sort & limit applied
	 */
	function sourceItems() {
		// get items (this may actually be a SS_List)
		$items = clone $this->getDataList();

		// TODO: Sorting could be implemented on regular SS_Lists.
		if(method_exists($items,'canSortBy') && isset($_REQUEST['ctf'][$this->getName()]['sort'])) {
    		$sort = $_REQUEST['ctf'][$this->getName()]['sort'];
		    // TODO: sort direction
			if($items->canSortBy($sort)) $items = $items->sort($sort);
		}

		// Determine pagination limit, offset
		// To disable pagination, set $this->showPagination to false.
		if($this->showPagination && $this->pageSize) {
		    $SQL_limit = (int)$this->pageSize;
		    if(isset($_REQUEST['ctf'][$this->getName()]['start']) && is_numeric($_REQUEST['ctf'][$this->getName()]['start'])) {
			    $SQL_start = (isset($_REQUEST['ctf'][$this->getName()]['start'])) ? intval($_REQUEST['ctf'][$this->getName()]['start']) : "0";
		    } else {
			    $SQL_start = 0;
		    }
		
		    $items = $items->limit($SQL_limit, $SQL_start);
	    }

		return $items;
	}

	/**
	 * Return a SS_List of TableListField_Item objects, suitable for display in the template.
	 */
	function Items() {
		$fieldItems = new ArrayList();
		if($items = $this->sourceItems()) foreach($items as $item) {
			if($item) $fieldItems->push(new $this->itemClass($item, $this));
		}
		return $fieldItems;
	}
	
	/**
	 * Returns the DataList for this field.
	 */
	function getDataList() {
		// If we weren't passed in a DataList to begin with, try and get the datalist from the form
		if($this->form && $this->getDataListFromForm) {
		    $this->getDataListFromForm = false;
			$relation = $this->name;
			if($record = $this->form->getRecord()) {
				if($record->hasMethod($relation)) $this->dataList = $record->$relation();
			}
		}
		
		if(!$this->dataList) {
			user_error(get_class($this). ' is missing a DataList', E_USER_ERROR);
		}
		
		return $this->dataList;
	}

	function getCsvDataList() {
		if($this->customCsvQuery) return $this->customCsvQuery;
		else return $this->getDataList();
	}
	
	/**
	 * @deprecated Use getDataList() instead.
	 */
	function getQuery() {
		Deprecation::notice('3.0', 'Use getDataList() instead.');
	    $list = $this->getDataList();
	    if(method_exists($list,'dataQuery')) {
		    return $this->getDataList()->dataQuery()->query();
	    }
	}

	/**
	 * @deprecated Use getCsvDataList() instead.
	 */
	function getCsvQuery() {
		Deprecation::notice('3.0', 'Use getCsvDataList() instead.');
	    $list = $this->getCsvDataList();
	    if(method_exists($list,'dataQuery')) {
            return $list->dataQuery()->query();
        }
	}
		
	function FieldList() {
		return $this->fieldList;
	}
	
	/**
	 * Configure this table to load content into a subform via ajax
	 */
	function setClick_AjaxLoad($urlBase, $formID) {
		$this->clickAction = "this.ajaxRequest('" . addslashes($urlBase) . "', '" . addslashes($formID) . "')";
		return $this;
	}

	/**
	 * Configure this table to open a popup window
	 */
	function setClick_PopupLoad($urlBase) {
		$this->clickAction = "var w = window.open(baseHref() + '$urlBase' + this.id.replace(/.*-(\d*)$/,'$1'), 'popup'); w.focus();";
		return $this;
	}
	
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setShowPagination(false);
		
		// Only include the show action if it was in the original CTF.
		$clone->setPermissions(in_array('show', $this->permissions) ? array('show') : array());

		$clone->addExtraClass( 'readonly' );
		$clone->setReadonly(true);
		return $clone;
	}
	
	/**
	 * #################################
	 *           CRUD
	 * #################################
	 */
	
	/**
	 * @return String
	 */
	function delete($request) {
		// Protect against CSRF on destructive action
		$token = $this->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError('400');
		
		if($this->Can('delete') !== true) {
			return false;
		}

		$this->methodName = "delete";
		
		$childId = Convert::raw2sql($_REQUEST['ctf']['childID']);

		if (is_numeric($childId)) {
		    $this->getDataList()->removeById($childId);
		}

		// TODO return status in JSON etc.
		//return $this->renderWith($this->template);
	}
	 
	 
	/**
	 * #################################
	 *           Summary-Row
	 * #################################
	 */
	 
	/**
	 * Can utilize some built-in summary-functions, with optional casting. 
	 * Currently supported:
	 * - sum
	 * - avg
	 * 
	 * @param $summaryTitle string
	 * @param $summaryFields array 
	 * Simple Format: array("MyFieldName"=>"sum")
	 * With Casting: array("MyFieldname"=>array("sum","Currency->Nice"))
	 */
	function addSummary($summaryTitle, $summaryFieldList) {
		$this->summaryTitle = $summaryTitle;
		$this->summaryFieldList = $summaryFieldList;
	}
	
	function removeSummary() {
		$this->summaryTitle = null;
		$this->summaryFields = null;
	}
	
	function HasSummary() {
		return (isset($this->summaryFieldList));
	}
	
	function SummaryTitle() {
		return $this->summaryTitle;
	}
	
	/**
	 * @param SS_List $items Only used to pass grouped sourceItems for creating
	 * partial summaries.
	 */
	function SummaryFields($items = null) {
		if(!isset($this->summaryFieldList)) {
			return false;
		}
		$summaryFields = array();
		$fieldListWithoutFirst = $this->fieldList;
		if(!empty($this->summaryTitle)) {
			array_shift($fieldListWithoutFirst);
		}
		foreach($fieldListWithoutFirst as $fieldName => $fieldTitle) {
			
			if(in_array($fieldName, array_keys($this->summaryFieldList))) {
				if(is_array($this->summaryFieldList[$fieldName])) {
					$summaryFunction = "colFunction_{$this->summaryFieldList[$fieldName][0]}";
					$casting = $this->summaryFieldList[$fieldName][1];
				} else {
					$summaryFunction = "colFunction_{$this->summaryFieldList[$fieldName]}";
					$casting = null;
				}

				// fall back to integrated sourceitems if not passed
				if(!$items) $items = $this->sourceItems();

				$summaryValue = ($items) ? $this->$summaryFunction($items->column($fieldName)) : null;
				
				// Optional casting, Format: array('MyFieldName'=>array('sum','Currency->Nice'))
				if(isset($casting)) {
					$summaryValue = $this->getCastedValue($summaryValue, $casting); 
				}
			} else {
				$summaryValue = null;
				$function = null;
			}
			
			$summaryFields[] = new ArrayData(array(
				'Function' => $function,
				'SummaryValue' => $summaryValue,
				'Name' => DBField::create_field('Varchar', $fieldName),
				'Title' => DBField::create_field('Varchar', $fieldTitle),
			));
		}
		return new ArrayList($summaryFields);
	}
	
	function HasGroupedItems() {
		return ($this->groupByField);	
	}
	
	function GroupedItems() {
		if(!$this->groupByField) {
			return false; 
		}
		
		$items = $this->sourceItems();
		if(!$items || !$items->Count()) {
			return false;
		}
		
		$groupedItems = $items->groupBy($this->groupByField);
		$groupedArrItems = new ArrayList();
		foreach($groupedItems as $key => $group) {
			$fieldItems = new ArrayList();
			foreach($group as $item) {
				if($item) $fieldItems->push(new $this->itemClass($item, $this));
			}
			$groupedArrItems->push(new ArrayData(array(
				'Items' => $fieldItems,
				'SummaryFields' => $this->SummaryFields($group)
			)));
		}
		
		return $groupedArrItems;
	}
	
	function colFunction_sum($values) {
		return array_sum($values);
	}

	function colFunction_avg($values) {
		return array_sum($values)/count($values);
	}
	
	 
	/**
	 * #################################
	 *           Permissions
	 * #################################
	 */
	
	/**
	 * Template accessor for Permissions.
	 * See {@link TableListField_Item->Can()} for object-specific
	 * permissions.
	 * 
	 * @return boolean
	 */
	function Can($mode) {
		if($mode == 'add' && $this->isReadonly()) {
			return false;
		} else if($mode == 'delete' && $this->isReadonly()) {
			return false;
		} else if($mode == 'edit' && $this->isReadonly()) {
			return false;
		} else {
			return (in_array($mode, $this->permissions));
		}
		
	}
	
	function setPermissions($arr) {
		$this->permissions = $arr;
		return $this;
	}

	/**
	 * @return array
	 */
	function getPermissions() {
		return $this->permissions;
	}

	/**
	 * #################################
	 *           Pagination
	 * #################################
	 */
	function setShowPagination($bool) {
		$this->showPagination = (bool)$bool;
		return $this;
	}

	/**
	 * @return boolean
	 */
	function ShowPagination() {
		if($this->showPagination && !empty($this->summaryFieldList)) {
			user_error("You can't combine pagination and summaries - please disable one of them.", E_USER_ERROR);
		}
		return $this->showPagination;
	}
	
	function setPageSize($pageSize) {
	 	$this->pageSize = $pageSize;
	 	return $this;
	}
	 
	 function PageSize() {
		return $this->pageSize;
	}
	 
	function ListStart() {
		return $_REQUEST['ctf'][$this->getName()]['start'];
	}
	
	/**
	 * @param array
	 * @deprecated Put the query string onto your form's link instead :-)
	 */
	function setExtraLinkParams($params){
		Deprecation::notice('2.4', 'Put the query string onto your FormAction instead().');
		$this->extraLinkParams = $params;
		return $this;
	}
	
	/**
	 * @return array
	 */
	function getExtraLinkParams(){
		return $this->extraLinkParams;
	}
	
	function FirstLink() {
		$start = 0;
		
		if(!isset($_REQUEST['ctf'][$this->getName()]['start']) || !is_numeric($_REQUEST['ctf'][$this->getName()]['start']) || $_REQUEST['ctf'][$this->getName()]['start'] == 0) {
			return null;
		}
		$baseLink = ($this->paginationBaseLink) ? $this->paginationBaseLink : $this->Link();
		$link = Controller::join_links($baseLink, "?ctf[{$this->getName()}][start]={$start}");
		if($this->extraLinkParams) $link .= "&" . http_build_query($this->extraLinkParams);
		
		// preserve sort options
		if(isset($_REQUEST['ctf'][$this->getName()]['sort'])) {
			$link .= "&ctf[{$this->getName()}][sort]=" . $_REQUEST['ctf'][$this->getName()]['sort'];
			// direction
			if(isset($_REQUEST['ctf'][$this->getName()]['dir'])) {
				$link .= "&ctf[{$this->getName()}][dir]=" . $_REQUEST['ctf'][$this->getName()]['dir'];
			}
		}
		
		return $link;
	}
	
	function PrevLink() {
		$currentStart = isset($_REQUEST['ctf'][$this->getName()]['start']) ? $_REQUEST['ctf'][$this->getName()]['start'] : 0;

		if($currentStart == 0) {
			return null;
		}
		
		$start = ($_REQUEST['ctf'][$this->getName()]['start'] - $this->pageSize < 0)  ? 0 : $_REQUEST['ctf'][$this->getName()]['start'] - $this->pageSize;
		
		$baseLink = ($this->paginationBaseLink) ? $this->paginationBaseLink : $this->Link();
		$link = Controller::join_links($baseLink, "?ctf[{$this->getName()}][start]={$start}");
		if($this->extraLinkParams) $link .= "&" . http_build_query($this->extraLinkParams);
		
		// preserve sort options
		if(isset($_REQUEST['ctf'][$this->getName()]['sort'])) {
			$link .= "&ctf[{$this->getName()}][sort]=" . $_REQUEST['ctf'][$this->getName()]['sort'];
			// direction
			if(isset($_REQUEST['ctf'][$this->getName()]['dir'])) {
				$link .= "&ctf[{$this->getName()}][dir]=" . $_REQUEST['ctf'][$this->getName()]['dir'];
			}
		}
		
		return $link;
	}
	

	function NextLink() {
		$currentStart = isset($_REQUEST['ctf'][$this->getName()]['start']) ? $_REQUEST['ctf'][$this->getName()]['start'] : 0;
		$start = ($currentStart + $this->pageSize < $this->TotalCount()) ? $currentStart + $this->pageSize : $this->TotalCount() % $this->pageSize > 0;
		if($currentStart >= $start-1) {
			return null;
		}
		$baseLink = ($this->paginationBaseLink) ? $this->paginationBaseLink : $this->Link();
		$link = Controller::join_links($baseLink, "?ctf[{$this->getName()}][start]={$start}");
		if($this->extraLinkParams) $link .= "&" . http_build_query($this->extraLinkParams);
		
		// preserve sort options
		if(isset($_REQUEST['ctf'][$this->getName()]['sort'])) {
			$link .= "&ctf[{$this->getName()}][sort]=" . $_REQUEST['ctf'][$this->getName()]['sort'];
			// direction
			if(isset($_REQUEST['ctf'][$this->getName()]['dir'])) {
				$link .= "&ctf[{$this->getName()}][dir]=" . $_REQUEST['ctf'][$this->getName()]['dir'];
			}
		}
		
		return $link;
	}
	
	function LastLink() {
		$pageSize = ($this->TotalCount() % $this->pageSize > 0) ? $this->TotalCount() % $this->pageSize : $this->pageSize;
		$start = $this->TotalCount() - $pageSize;
		// Check if there is only one page, or if we are on last page
		if($this->TotalCount() <= $pageSize || (isset($_REQUEST['ctf'][$this->getName()]['start']) &&  $_REQUEST['ctf'][$this->getName()]['start'] >= $start)) {
			return null;
		}
		
		$baseLink = ($this->paginationBaseLink) ? $this->paginationBaseLink : $this->Link();
		$link = Controller::join_links($baseLink, "?ctf[{$this->getName()}][start]={$start}");
		if($this->extraLinkParams) $link .= "&" . http_build_query($this->extraLinkParams);
		
		// preserve sort options
		if(isset($_REQUEST['ctf'][$this->getName()]['sort'])) {
			$link .= "&ctf[{$this->getName()}][sort]=" . $_REQUEST['ctf'][$this->getName()]['sort'];
			// direction
			if(isset($_REQUEST['ctf'][$this->getName()]['dir'])) {
				$link .= "&ctf[{$this->getName()}][dir]=" . $_REQUEST['ctf'][$this->getName()]['dir'];
			}
		}
		
		return $link;
	}
	
	function FirstItem() {
		if ($this->TotalCount() < 1) return 0;
		return isset($_REQUEST['ctf'][$this->getName()]['start']) ? $_REQUEST['ctf'][$this->getName()]['start'] + 1 : 1;
	}
	
	function LastItem() {
		if(isset($_REQUEST['ctf'][$this->getName()]['start'])) {
			return $_REQUEST['ctf'][$this->getName()]['start'] + min($this->pageSize, $this->TotalCount() - $_REQUEST['ctf'][$this->getName()]['start']);
		} else {
			return min($this->pageSize, $this->TotalCount());
		}
	}

	/**
	 * @ignore
	 */
	private $_cache_TotalCount;

	/**
	 * Return the total number of items in the source DataList
	 */
	function TotalCount() {
	    if($this->_cache_TotalCount === null) {
	        $this->_cache_TotalCount = $this->getDataList()->Count();
	    }
		return $this->_cache_TotalCount;
	}
	
	
	/**
	 * #################################
	 *           Search
	 * #################################
	 * 
	 * @todo Not fully implemented at the moment
	 */
	 
	 /**
	  * Compile all request-parameters for search and pagination
	  * (except the actual list-positions) as a query-string.
	  * 
	  * @return String URL-parameters
	  */
	function filterString() {
		
	}
	
	
	
	/**
	 * #################################
	 *           CSV Export
	 * #################################
	 */
	 function setFieldListCsv($fields) {
	 	$this->fieldListCsv = $fields;
	 	return $this;
	 }
	
	/**
	 * Set the CSV separator character.  Defaults to ,
	 */
	function setCsvSeparator($csvSeparator) {
		$this->csvSeparator = $csvSeparator;
		return $this;
	}
	
	/**
	 * Get the CSV separator character.  Defaults to ,
	 */
	function getCsvSeparator() {
		return $this->csvSeparator;
	}
	
	/**
	 * Remove the header row from the CSV export
	 */
	function removeCsvHeader() {
		$this->csvHasHeader = false;
		return $this;
	}
	 
	/**
	 * Exports a given set of comma-separated IDs (from a previous search-query, stored in a HiddenField).
	 * Uses {$csv_columns} if present, and falls back to {$result_columns}.
	 * We move the most filedata generation code to the function {@link generateExportFileData()} so that a child class
	 * could reuse the filedata generation code while overwrite export function.
	 * 
	 * @todo Make relation-syntax available (at the moment you'll have to use custom sql) 
	 */
	function export() {
		$now = date("d-m-Y-H-i");
		$fileName = "export-$now.csv";

		// No pagination for export
		$oldShowPagination = $this->showPagination;
		$this->showPagination = false;
		
		$result = $this->renderWith(array($this->template . '_printable', 'TableListField_printable'));
		
		$this->showPagination = $oldShowPagination;
		
		if($fileData = $this->generateExportFileData($numColumns, $numRows)){
			return SS_HTTPRequest::send_file($fileData, $fileName, 'text/csv');
		} else {
			user_error("No records found", E_USER_ERROR);
		}
	}
	
	function generateExportFileData(&$numColumns, &$numRows) {
		$separator = $this->csvSeparator;
		$csvColumns = ($this->fieldListCsv) ? $this->fieldListCsv : $this->fieldList;
		$fileData = '';
		$columnData = array();
		$fieldItems = new ArrayList();
		
		if($this->csvHasHeader) {
			$fileData .= "\"" . implode("\"{$separator}\"", array_values($csvColumns)) . "\"";
			$fileData .= "\n";
		}

		if(isset($this->customSourceItems)) {
			$items = $this->customSourceItems;
		} else {
			$items = $this->getCsvDataList();
		}
		
		// temporary override to adjust TableListField_Item behaviour
		$this->setFieldFormatting(array());
		$this->fieldList = $csvColumns;

		if($items) {
			foreach($items as $item) {
				if(is_array($item)) {
					$className = isset($item['RecordClassName']) ? $item['RecordClassName'] : $item['ClassName'];
					$item = new $className($item);
				}
				$fieldItem = new $this->itemClass($item, $this);
				
				$fields = $fieldItem->Fields(false);
				$columnData = array();
				if($fields) foreach($fields as $field) {
					$value = $field->Value;
					
					// TODO This should be replaced with casting
					if(array_key_exists($field->Name, $this->csvFieldFormatting)) {
						$format = str_replace('$value', "__VAL__", $this->csvFieldFormatting[$field->Name]);
						$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$item->$1', $format);
						$format = str_replace('__VAL__', '$value', $format);
						eval('$value = "' . $format . '";');
					}
					
					$value = str_replace(array("\r", "\n"), "\n", $value);
					$tmpColumnData = '"' . str_replace('"', '\"', $value) . '"';
					$columnData[] = $tmpColumnData;
				}
				$fileData .= implode($separator, $columnData);
				$fileData .= "\n";
				
				$item->destroy();
				unset($item);
				unset($fieldItem);
			}
			
			$numColumns = count($columnData);
			$numRows = $fieldItems->count();
			return $fileData;
		} else {
			return null;
		}
	}
	
	/**
	 * We need to instanciate this button manually as a normal button has no means of adding inline onclick-behaviour.
	 */
	function ExportLink() {
		$exportLink = Controller::join_links($this->Link(), 'export');
		
		if($this->extraLinkParams) $exportLink .= "?" . http_build_query($this->extraLinkParams);
		return $exportLink;
	}

	function printall() {
		Requirements::clear();
		if(defined('CMS_DIR')) {
			Requirements::css(CMS_DIR . '/css/typography.css');
			Requirements::css(CMS_DIR . '/css/cms_right.css');
		}
		Requirements::css('sapphire/css/TableListField_print.css');
		
		$this->cachedSourceItems = null;
		$oldShowPagination = $this->showPagination;
		$this->showPagination = false;

		increase_time_limit_to();
		$this->Print = true;
		
		$result = $this->renderWith(array($this->template . '_printable', 'TableListField_printable'));
		
		$this->showPagination = $oldShowPagination;
		
		return $result;
	}

	function PrintLink() {
		$link = Controller::join_links($this->Link(), 'printall');
		if(isset($_REQUEST['ctf'][$this->getName()]['sort'])) {
			$link = HTTP::setGetVar("ctf[{$this->getName()}][sort]",Convert::raw2xml($_REQUEST['ctf'][$this->getName()]['sort']), $link);
		}
		return $link;
	}
	
	/**
	 * #################################
	 *           Utilty
	 * #################################
	 */
	function Utility() {
		$links = new ArrayList();
		if($this->can('export')) {
			$links->push(new ArrayData(array(
				'Title' => _t('TableListField.CSVEXPORT', 'Export to CSV'),
				'Link' => $this->ExportLink()
			)));
		}
		if($this->can('print')) {
			$links->push(new ArrayData(array(
				'Title' => _t('TableListField.PRINT', 'Print'),
				'Link' => $this->PrintLink()
			)));
		}
		return $links;
		
	}
	
	function setFieldCasting($casting) {
		$this->fieldCasting = $casting;
		return $this;
	}

	function setFieldFormatting($formatting) {
		$this->fieldFormatting = $formatting;
		return $this;
	}
	
	function setCSVFieldFormatting($formatting) {
		$this->csvFieldFormatting = $formatting;
		return $this;
	}
	
	/**
	 * Edit the field list
	 */
	function setFieldList($fieldList) {
		$this->fieldList = $fieldList;
		return $this;
	}
	
	/**
	 * @return String
	 */
	function Name() {
		return $this->name;
	}
	
	function Title() {
	  // adding translating functionality
	  // this is a bit complicated, because this parameter is passed to this class
	  // and should come here translated already
	  // adding this to TODO probably add a method to the classes
	  // to return they're translated string
	  // added by ruibarreiros @ 27/11/2007
		return $this->sourceClass() ? singleton($this->sourceClass())->singular_name() : $this->getName();
	}
	
	function NameSingular() {
	  // same as Title()
	  // added by ruibarreiros @ 27/11/2007
	  return $this->sourceClass() ? singleton($this->sourceClass())->singular_name() : $this->getName();
	}

	function NamePlural() {
	  // same as Title()
	  // added by ruibarreiros @ 27/11/2007
		return $this->sourceClass() ? singleton($this->sourceClass())->plural_name() : $this->getName();
	} 
	
	function setTemplate($template) {
		$this->template = $template;
		return $this;
	}
	
	function CurrentLink() {
		$link = $this->Link();
		
		if(isset($_REQUEST['ctf'][$this->getName()]['start']) && is_numeric($_REQUEST['ctf'][$this->getName()]['start'])) {
			$start = ($_REQUEST['ctf'][$this->getName()]['start'] < 0)  ? 0 : $_REQUEST['ctf'][$this->getName()]['start'];
			$link = Controller::join_links($link, "?ctf[{$this->getName()}][start]={$start}");
		}

		if($this->extraLinkParams) $link .= "&" . http_build_query($this->extraLinkParams);
		
		return $link;
	}

	/**
	 * Overloaded to automatically add security token.
	 * 
	 * @param String $action
	 * @return String
	 */
	function Link($action = null) {
		$form = $this->getForm();
 		if($form) {
			$token = $form->getSecurityToken();
			$parentUrlParts = parse_url(parent::Link($action));
			$queryPart = (isset($parentUrlParts['query'])) ? '?' . $parentUrlParts['query'] : null;
			// Ensure that URL actions not routed through Form->httpSubmission() are protected against CSRF attacks.
			if($token->isEnabled()) $queryPart = $token->addtoUrl($queryPart);
			return Controller::join_links($parentUrlParts['path'], $queryPart);
		} else {
			// allow for instanciation of this FormField outside of a controller/form
			// context (e.g. for unit tests)
			return false;
		}
	}
	
	function BaseLink() {
		user_error("TableListField::BaseLink() deprecated, use Link() instead", E_USER_NOTICE);
		return $this->Link();
	}
	
	/**
	 * Helper method to determine permissions for a scaffolded
	 * TableListField (or subclasses) - currently used in {@link ModelAdmin} and {@link DataObject->scaffoldFormFields()}.
	 * Returns true for each permission that doesn't have an explicit getter.
	 * 
	 * @todo Temporary method, implement directly in FormField subclasses with object-level permissions.
	 *
	 * @param string $class
	 * @param numeric $id
	 * @return array
	 */
	public static function permissions_for_object($class, $id = null) {
		$permissions = array();
		$obj = ($id) ? DataObject::get_by_id($class, $id) : singleton($class);
		
		if(!$obj->hasMethod('canView') || $obj->canView()) $permissions[] = 'show';
		if(!$obj->hasMethod('canEdit') || $obj->canEdit()) $permissions[] = 'edit';
		if(!$obj->hasMethod('canDelete') || $obj->canDelete()) $permissions[] = 'delete';
		if(!$obj->hasMethod('canCreate') || $obj->canCreate()) $permissions[] = 'add';
		
		return $permissions;
	}

	/**
	 * @param $value
	 * 
	 */
	function getCastedValue($value, $castingDefinition) {
		if(is_array($castingDefinition)) {
			$castingParams = $castingDefinition;
			array_shift($castingParams);
			$castingDefinition = array_shift($castingDefinition);
		} else {
			$castingParams = array();
		}
		if(strpos($castingDefinition,'->') === false) {
			$castingFieldType = $castingDefinition;
			$castingField = DBField::create_field($castingFieldType, $value);
			$value = call_user_func_array(array($castingField,'XML'),$castingParams);
		} else {
			$fieldTypeParts = explode('->', $castingDefinition);
			$castingFieldType = $fieldTypeParts[0];	
			$castingMethod = $fieldTypeParts[1];
			$castingField = DBField::create_field($castingFieldType, $value);
			$value = call_user_func_array(array($castingField,$castingMethod),$castingParams);
		}
		
		return $value;
	}	 
	 
	function setHighlightConditions($conditions) {
		$this->highlightConditions = $conditions;
		return $this;
	}
	
	/**
	 * See {@link SelectOptions()} for introduction.
	 * 
	 * @param $options array Options to add, key being a unique identifier of the action,
	 *  and value a title for the rendered link element (can contain HTML).
	 *  The keys for 'all' and 'none' have special behaviour associated
	 *  through TableListField.js JavaScript.
	 *  For any other key, the JavaScript automatically checks all checkboxes contained in
	 *  <td> elements with a matching classname.
	 */
	function addSelectOptions($options){
		foreach($options as $k => $title)
		$this->selectOptions[$k] = $title;
	}
	
	/**
	 * Remove one all more table's {@link $selectOptions}
	 * 
	 * @param $optionsNames array
	 */
	function removeSelectOptions($names){
		foreach($names as $name){
			unset($this->selectOptions[trim($name)]);
		}
	}
	
	/**
	 * Return the table's {@link $selectOptions}.
	 * Used to toggle checkboxes for each table row through button elements.
	 * 
	 * Requires {@link Markable()} to return TRUE.
	 * This is only functional with JavaScript enabled.
	 * 
	 * @return SS_List of ArrayData objects
	 */
	function SelectOptions(){
		if(!$this->selectOptions) return;
		
		$selectOptionsSet = new ArrayList();
		foreach($this->selectOptions as $k => $v) {
			$selectOptionsSet->push(new ArrayData(array(
				'Key' => $k,
				'Value' => $v
			)));
		}
		return $selectOptionsSet;
	}
}

/**
 * A single record in a TableListField.
 * @package forms
 * @subpackage fields-relational
 * @see TableListField
 */
class TableListField_Item extends ViewableData {
	
	/**
	 * @var DataObject The underlying data record,
	 * usually an element of {@link TableListField->sourceItems()}.
	 */
	protected $item;
	
	/**
	 * @var TableListField
	 */
	protected $parent;
	
	function __construct($item, $parent) {
		$this->failover = $this->item = $item;
		$this->parent = $parent;
		parent::__construct();
	}
	
	function ID() {
		return $this->item->ID;
	}
	
	function Parent() {
		return $this->parent;
	}
	
	function Fields($xmlSafe = true) {
		$list = $this->parent->FieldList();
		foreach($list as $fieldName => $fieldTitle) {
			$value = "";

			// This supports simple FieldName syntax
			if(strpos($fieldName,'.') === false) {
				$value = ($this->item->XML_val($fieldName) && $xmlSafe) ? $this->item->XML_val($fieldName) : $this->item->RAW_val($fieldName);
			// This support the syntax fieldName = Relation.RelatedField
			} else {					
				$fieldNameParts = explode('.', $fieldName)	;
				$tmpItem = $this->item;
				for($j=0;$j<sizeof($fieldNameParts);$j++) {
					$relationMethod = $fieldNameParts[$j];
					$idField = $relationMethod . 'ID';
					if($j == sizeof($fieldNameParts)-1) {
						if($tmpItem) $value = $tmpItem->$relationMethod;
					} else {
						if($tmpItem) $tmpItem = $tmpItem->$relationMethod();
					}
				}
			}
			
			// casting
			if(array_key_exists($fieldName, $this->parent->fieldCasting)) {
				$value = $this->parent->getCastedValue($value, $this->parent->fieldCasting[$fieldName]);
			} elseif(is_object($value) && method_exists($value, 'Nice')) {
				$value = $value->Nice();
			}
			
			// formatting
			$item = $this->item;
			if(array_key_exists($fieldName, $this->parent->fieldFormatting)) {
				$format = str_replace('$value', "__VAL__", $this->parent->fieldFormatting[$fieldName]);
				$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$item->$1', $format);
				$format = str_replace('__VAL__', '$value', $format);
				eval('$value = "' . $format . '";');
			}
			
			//escape
			if($escape = $this->parent->fieldEscape){
				foreach($escape as $search => $replace){
					$value = str_replace($search, $replace, $value);
				}
			}
			
			$fields[] = new ArrayData(array(
				"Name" => $fieldName, 
				"Title" => $fieldTitle,
				"Value" => $value,
				"CsvSeparator" => $this->parent->getCsvSeparator(),
			));
		}
		return new ArrayList($fields);
	}
	
	function Markable() {
		return $this->parent->Markable;
	}
	
	/**
	 * Checks global permissions for field in  {@link TableListField->Can()}.
	 * If they are allowed, it checks for object permissions by assuming
	 * a method with "can" + $mode parameter naming, e.g. canDelete().
	 * 
	 * @param string $mode See {@link TableListField::$permissions} array.
	 * @return boolean
	 */
	function Can($mode) {
		$canMethod = "can" . ucfirst($mode);
		if(!$this->parent->Can($mode)) {
			// check global settings for the field instance
			return false;
		} elseif($this->item->hasMethod($canMethod)) {
			// if global allows, check object specific permissions (e.g. canDelete())
			return $this->item->$canMethod();
		} else {
			// otherwise global allowed this action, so return TRUE
			return true;
		}
	}
	
	function Link($action = null) {
		$form = $this->parent->getForm();
 		if($form) {
			$token = $form->getSecurityToken();
			$parentUrlParts = parse_url($this->parent->Link());
			$queryPart = (isset($parentUrlParts['query'])) ? '?' . $parentUrlParts['query'] : null;
			// Ensure that URL actions not routed through Form->httpSubmission() are protected against CSRF attacks.
			if($token->isEnabled()) $queryPart = $token->addtoUrl($queryPart);
			return Controller::join_links($parentUrlParts['path'], 'item', $this->item->ID, $action, $queryPart);
		} else {
			// allow for instanciation of this FormField outside of a controller/form
			// context (e.g. for unit tests)
			return false;
		}
	}

	/**
	 * Returns all row-based actions not disallowed through permissions.
	 * See TableListField->Action for a similiar dummy-function to work
	 * around template-inheritance issues.
	 * 
	 * @return SS_List
	 */
	function Actions() {
		$allowedActions = new ArrayList();
		foreach($this->parent->actions as $actionName => $actionSettings) {
			if($this->parent->Can($actionName)) {
				$allowedActions->push(new ArrayData(array(
					'Name' => $actionName,
					'Link' => $this->{ucfirst($actionName).'Link'}(),
					'Icon' => $actionSettings['icon'],
					'IconDisabled' => $actionSettings['icon_disabled'],
					'Label' => $actionSettings['label'],
					'Class' => $actionSettings['class'],
					'Default' => ($actionName == $this->parent->defaultAction),
					'IsAllowed' => $this->Can($actionName), 
				)));
			}
		}
		
		return $allowedActions;
	}
   
	function BaseLink() {
		user_error("TableListField_Item::BaseLink() deprecated, use Link() instead", E_USER_NOTICE);
		return $this->Link();
	}

	function DeleteLink() {
		return Controller::join_links($this->Link(), "delete");
	}
	
	function MarkingCheckbox() {
		$name = $this->parent->getName() . '[]';
		
		if($this->parent->isReadonly())
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\" />";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" />";
	}
	
	/**
	 * According to {@link TableListField->selectOptions}, each record will check if the options' key on the object is true,
	 * if it is true, add the key as a class to the record
	 * 
	 * @return string Value for a 'class' HTML attribute.
	 */
	function SelectOptionClasses(){
		$tagArray = array('markingcheckbox');
		$options = $this->parent->SelectOptions();
		if($options && $options->exists()){
			foreach($options as $option){
				if($option->Key !== 'all' && $option->Key !== 'none'){
					if($this->{$option->Key}) {
						$tagArray[] = $option->Key;
					}
				}
			}
		}
		return implode(" ",$tagArray);
	}
	
	function HighlightClasses() {
		$classes = array();
		foreach($this->parent->highlightConditions as $condition) {
			$rule = str_replace("\$","\$this->item->", $condition['rule']);
			$ruleApplies = null;
			eval('$ruleApplies = ('.$rule.');');
			if($ruleApplies) {
				if(isset($condition['exclusive']) && $condition['exclusive']) {
					return $condition['class'];
				} else {
					$classes[] = $condition['class']; 					
				}
			}
		}
		
		return (count($classes) > 0) ? " " . implode(" ", $classes) : false;
	}
		
	/**
	 * Legacy: Please use permissions instead
	 */
	function isReadonly() {
		return $this->parent->Can('delete');
	}
}

/**
 * @package forms
 * @subpackage fields-relational
 */
class TableListField_ItemRequest extends RequestHandler {
	protected $ctf;
	protected $itemID;
	protected $methodName;
	
	static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'index',
	);
	
	function Link() {
		return Controller::join_links($this->ctf->Link(), 'item/' . $this->itemID);
	}
	
	function __construct($ctf, $itemID) {
		$this->ctf = $ctf;
		$this->itemID = $itemID;
		
		parent::__construct();
	}

	function delete($request) {
		// Protect against CSRF on destructive action
		$token = $this->ctf->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError('400');
		
		if($this->ctf->Can('delete') !== true) {
			return false;
		}

		$this->dataObj()->delete();
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Return the data object being manipulated
	 */
	function dataObj() {
		// used to discover fields if requested and for population of field
		if(is_numeric($this->itemID)) {
 			// we have to use the basedataclass, otherwise we might exclude other subclasses 
 			return $this->ctf->getDataList()->byId($this->itemID);
		}
		
	}

	/**
	 * @return TableListField
	 */
	function getParentController() {
		return $this->ctf;
	}
}

