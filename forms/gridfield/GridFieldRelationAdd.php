<?php
/**
 * A GridFieldRelationAdd is responsible for adding objects to another objects
 * has_many and many_many relation. It will not attach duplicate objects.
 *
 * It augments a GridField with fields above the gridfield to search and add
 * objects to whatever the SS_List passed into the gridfield.
 *
 * If the object is set to use autosuggestion it will include jQuery UI
 * autosuggestion field that searches for current objects that isn't already
 * attached to the list.
 */
class GridFieldRelationAdd implements GridField_HTMLProvider, GridField_ActionProvider, GridField_DataManipulator, GridField_URLHandler {
	
	/**
	 * Which template to use for rendering
	 * 
	 * @var string $itemClass
	 */
	protected $itemClass = 'GridFieldRelationAdd';
	
	/**
	 * Which column that should be used for doing a StartsWith search
	 *
	 * @var string
	 */
	protected $fieldToSearch = '';
	
	/**
	 * Use the jQuery.ui.autosuggestion plugin
	 *
	 * @var bool
	 */
	protected $useAutoSuggestion = true;
	
	/**
	 *
	 * @param string $fieldToSearch which field on the object in the list should be search
	 * @param bool $autoSuggestion - if you would like to use the javascript autosuggest feature
	 */
	public function __construct($fieldToSearch, $autoSuggestion=true) {
		$this->fieldToSearch = $fieldToSearch;
		$this->useAutoSuggestion = $autoSuggestion;
	}
	
	/**
	 * 
	 * @param GridField $gridField
	 * @return string - HTML
	 */
	public function getHTMLFragments($gridField) {
		$searchState = $gridField->State->GridFieldSearchRelation;
		
		
		if($this->useAutoSuggestion){
			Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
			Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/jquery-ui.js');
			Requirements::javascript(SAPPHIRE_DIR . "/javascript/GridFieldSearch.js");
		}
		
		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList();
		
		$value = $this->findSingleEntry($gridField, $this->fieldToSearch, $searchState, $gridField->getList()->dataClass);
		$searchField = new TextField('gridfield_relationsearch', _t('GridField.RelationSearch', "Relation search"), $value);
		// Apparently the data-* needs to be double qouted for the jQuery.meta data plugin
		$searchField->setAttribute('data-search-url', '\''.Controller::join_links($gridField->Link('search').'\''));
		$searchField->addExtraClass('relation-search');
		
		$findAction = new GridField_Action($gridField, 'gridfield_relationfind', _t('GridField.Find', "Find"), 'find', 'find');
		$addAction = new GridField_Action($gridField, 'gridfield_relationadd', _t('GridField.Add', "Add"), 'addto', 'addto');

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
		$results = $allList->subtract($gridField->getList())->filter($this->fieldToSearch.':StartsWith',$request->param('ID'));
		$results->sort($this->fieldToSearch, 'ASC');
		
		$json = array();
		foreach($results as $result) {
			$json[$result->ID] = $result->{$this->fieldToSearch};
		}
		return Convert::array2json($json);
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