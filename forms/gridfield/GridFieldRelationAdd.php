<?php
/**
 * A GridFieldRelationAdd is responsible for adding objects to another object's has_many and many_many relation,
 * as defined by the RelationList passed to the GridField constructor.
 * Objects can be searched through an input field (partially matching one or more fields).
 * Selecting from the results will add the object to the relation.
 */
class GridFieldRelationAdd implements GridField_HTMLProvider, GridField_ActionProvider, GridField_DataManipulator, GridField_URLHandler {
	
	/**
	 * Which template to use for rendering
	 * 
	 * @var string $itemClass
	 */
	protected $itemClass = 'GridFieldRelationAdd';
	
	/**
	 * Which columns that should be used for doing a "StartsWith" search.
	 * If multiple fields are provided, the filtering is performed non-exclusive.
	 *
	 * @var Array
	 */
	protected $searchFields = array();

	/**
	 * @var string SSViewer template to render the results presentation
	 */
	protected $resultsFormat = '$Title';

	/**
	 * @var String Text shown on the search field, instructing what to search for.
	 */
	protected $placeholderText;
	
	/**
	 *
	 * @param array $searchFields Which fields on the object in the list should be searched
	 */
	public function __construct($searchFields) {
		$this->searchFields = (array)$searchFields;
	}
	
	/**
	 * 
	 * @param GridField $gridField
	 * @return string - HTML
	 */
	public function getHTMLFragments($gridField) {
		$searchState = $gridField->State->GridFieldSearchRelation;
		
		
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/jquery-ui.js');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/GridFieldSearch.js");
		
		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList();
		
		$value = $this->findSingleEntry($gridField, $this->searchFields, $searchState, $gridField->getList()->dataClass);
		$searchField = new TextField('gridfield_relationsearch', _t('GridField.RelationSearch', "Relation search"), $value);
		// Apparently the data-* needs to be double qouted for the jQuery.meta data plugin
		$searchField->setAttribute('data-search-url', '\''.Controller::join_links($gridField->Link('search').'\''));
		$searchField->setAttribute('placeholder', $this->getPlaceholderText($gridField->getList()->dataClass()));
		$searchField->addExtraClass('relation-search');
		
		$findAction = new GridField_Action($gridField, 'gridfield_relationfind', _t('GridField.Find', "Find"), 'find', 'find');
		$findAction->setButtonIcon('relationfind');
		$addAction = new GridField_Action($gridField, 'gridfield_relationadd', _t('GridField.LinkExisting', "Link Exisiting"), 'addto', 'addto');
		$addAction->setButtonIcon('linkexisting');

		// If an object is not found, disable the action
		if(!is_int($gridField->State->GridFieldAddRelation)) {
			$addAction->setReadonly(true);
		}
		
		$forTemplate->Fields->push($searchField);
		$forTemplate->Fields->push($findAction);
		$forTemplate->Fields->push($addAction);
		return array('before' => $forTemplate->renderWith($this->itemClass));
	}
	
	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		return array('addto', 'find');
	}

	/**
	 * Manipulate the state to either add a new relation, or doing a small search
	 * 
	 * @param GridField $gridField
	 * @param string $actionName
	 * @param string $arguments
	 * @param string $data
	 * @return string
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		switch($actionName) {
			case 'addto':
				if(isset($data['relationID']) && $data['relationID']){
					$gridField->State->GridFieldAddRelation = $data['relationID'];
				}
				$gridField->State->GridFieldSearchRelation = '';
				break;
			case 'find' && isset($data['autosuggest_search']):
				$gridField->State->GridFieldSearchRelation = $data['autosuggest_search'];
				break;
		}
	}
	
	/**
	 * If an object ID is set, add the object to the list
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		if(!$gridField->State->GridFieldAddRelation) {
			return $dataList;
		}
		$objectID = Convert::raw2sql($gridField->State->GridFieldAddRelation);
		if($objectID) {
			$object = DataObject::get_by_id($dataList->dataclass(), $objectID);
			if($object) {
				$dataList->add($object);
			}
		}
		$gridField->State->GridFieldAddRelation = null;
		return $dataList;
	}
	
	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getURLHandlers($gridField) {
		return array(
			'search/$ID' => 'doSearch',
		);
	}
	
	/**
	 * Returns a json array of a search results that can be used by for example Jquery.ui.autosuggestion
	 *
	 * @param GridField $gridField
	 * @param SS_HTTPRequest $request 
	 */
	public function doSearch($gridField, $request) {
		$allList = DataList::create($gridField->getList()->dataClass());
		$filters = array();
		$stmts = array();
		// TODO Replace with DataList->filterAny() once it correctly supports OR connectives
		foreach($this->searchFields as $searchField) {
			$stmts[] .= sprintf('"%s" LIKE \'%s%%\'', $searchField, $request->param('ID'));
		}
		$results = $allList->where(implode(' OR ', $stmts))->subtract($gridField->getList());
		$results->sort($this->searchFields[0], 'ASC');
		
		$json = array();
		foreach($results as $result) {
			$json[$result->ID] = SSViewer::fromString($this->resultsFormat)->process($result);
		}
		return Convert::array2json($json);
	}

	/**
	 * @param String
	 */
	public function setResultsFormat($format) {
		$this->resultsFormat = $format;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getResultsFormat() {
		return $this->resultsFormat;
	}

	/**
	 * @param Array
	 */
	public function setSearchFields($fields) {
		$this->searchFields = $fields;
		return $this;
	}

	/**
	 * @return Array
	 */
	public function getSearchFields() {
		return $this->searchFields;
	}

	/**
	 * @param String The class of the object being searched for
	 * @return String
	 */
	public function getPlaceholderText($dataClass) {
		if($this->placeholderText) {
			return $this->placeholderText;
		} else {
			$labels = array();
			foreach($this->searchFields as $searchField) {
				$label = singleton($dataClass)->fieldLabel($searchField);
				if($label) $labels[] = $label;
			}
			if($labels) {
				return sprintf(
					_t('GridField.PlaceHolderWithLabels', 'Find %s by %s', PR_MEDIUM, 'Find <object type> by <field names>'), 
					singleton($dataClass)->plural_name(),
					implode(', ', $labels)
				);
			} else {
				return sprintf(
					_t('GridField.PlaceHolder', 'Find %s', PR_MEDIUM, 'Find <object type>'), 
					singleton($dataClass)->plural_name()
				);
			}
		}
	}

	/**
	 * @param String
	 */
	public function setPlaceholderText($text) {
		$this->placeholderText = $text;
	}
	
	/**
	 * This will provide a StartsWith search that only returns a value if we are
	 * matching ONE object only. We wouldn't want to attach used any object to
	 * the list.
	 * 
	 * @param GridField $gridField
	 * @param string $field
	 * @param string $searchTerm
	 * @param string $dataclass
	 * @return string 
	 */
	protected function findSingleEntry($gridField, $field, $searchTerm, $dataclass) {
		$fullList = DataList::create($dataclass);
		$searchTerm = Convert::raw2sql($searchTerm);
		if(!$searchTerm) {
			return;
		}
		$existingList = clone $gridField->getList();
		$searchResults = $fullList->subtract($existingList->limit(0))->filter($field.':StartsWith', $searchTerm);
		
		// If more than one, skip
		if($searchResults->count() != 1) {
			return '';
		}
		
		$gridField->State->GridFieldAddRelation = $searchResults->first()->ID;
		return $searchResults->first()->$field;
	}
}