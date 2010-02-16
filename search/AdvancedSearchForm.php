<?php
/**
 * More advanced search form
 * @package sapphire
 * @subpackage search
 */
class AdvancedSearchForm extends SearchForm {
	
	/**
	 * the constructor of a Simple/basic SearchForm
	 */
	function __construct($controller, $name, $fields = null, $actions = null) {
		if(!$fields) {
			$fields = new FieldSet(
				$searchBy = new CompositeField(
					new HeaderField('SearchByHeader',_t('AdvancedSearchForm.SEARCHBY', 'SEARCH BY')),
					new TextField("+", _t('AdvancedSearchForm.ALLWORDS', 'All Words')),
					new TextField("quote", _t('AdvancedSearchForm.EXACT', 'Exact Phrase')),
					new TextField("any", _t('AdvancedSearchForm.ATLEAST', 'At Least One Of the Words')),
					new TextField("-", _t('AdvancedSearchForm.WITHOUT', 'Without the Words'))
				),
				$sortBy = new CompositeField(
					new HeaderField('SortByHeader',_t('AdvancedSearchForm.SORTBY', 'SORT RESULTS BY')),
					new OptionsetField("sortby", "",
						array(
						        'Relevance' => _t('AdvancedSearchForm.RELEVANCE', 'Relevance'),
							'LastUpdated' => _t('AdvancedSearchForm.LASTUPDATED', 'Last Updated'),
							'PageTitle' => _t('AdvancedSearchForm.PAGETITLE', 'Page Title'),
						),
						'Relevance'
					)
				),
				$chooseDate = new CompositeField(
					new HeaderField('LastUpdatedHeader',_t('AdvancedSearchForm.LASTUPDATEDHEADER', 'LAST UPDATED')),
					new DateField("From", _t('AdvancedSearchForm.FROM', 'From')),
					new DateField("To", _t('AdvancedSearchForm.TO', 'To'))
				)					
			);
			
			$searchBy->ID = "AdvancedSearchForm_SearchBy";
			$searchOnly->ID = "AdvancedSearchForm_SearchOnly";
			$sortBy->ID = "AdvancedSearchForm_SortBy";
			$chooseDate->ID = "AdvancedSearchForm_ChooseDate";
		}
		
		if(!$actions) {
			$actions = new FieldSet(
				new FormAction("results", _t('AdvancedSearchForm.GO', 'Go'))
			);
		}
		parent::__construct($controller, $name, $fields, $actions);
	}

	public function forTemplate(){
		return $this->renderWith(array("AdvancedSearchForm","Form"));
	}
	
	/* Return dataObjectSet of the results, using the form data.
	 */
	public function getResults($numPerPage = 10) {
		$data = $this->getData();

	 	if($data['+']) $keywords .= " +" . ereg_replace(" +", " +", trim($data['+']));
	 	if($data['quote']) $keywords .= ' "' . $data['quote'] . '"';
	 	if($data['any']) $keywords .= ' ' . $data['any'];
	 	if($data['-']) $keywords .= " -" . ereg_replace(" +", " -", trim($data['-']));
	 	$keywords = trim($keywords);
	 	
	 	// This means that they want to just find pages where there's *no* match
	 	
	 	if($keywords[0] == '-') {
	 		$keywords = $data['-'];
	 		$invertedMatch = true;
	 	}

	 	
	 	// Limit search to various sections
	 	if($_REQUEST['OnlyShow']) {
	 		$pageList = array();

			// Find the associated pages	 		
	 		foreach($_REQUEST['OnlyShow'] as $section => $checked) {
	 			$items = explode(",", $section);
	 			foreach($items as $item) {
	 				$page = DataObject::get_one('SiteTree', "\"URLSegment\" = '" . DB::getConn()->addslashes($item) . "'");
	 				$pageList[] = $page->ID;
	 				if(!$page) user_error("Can't find a page called '$item'", E_USER_WARNING);
	 				$page->loadDescendantIDListInto($pageList);
	 			}
	 		}	
	 		$contentFilter = "\"ID\" IN (" . implode(",", $pageList) . ")";
	 		
	 		// Find the files associated with those pages
	 		$fileList = DB::query("SELECT \"FileID\" FROM \"Page_ImageTracking\" WHERE \"PageID\" IN (" . implode(",", $pageList) . ")")->column();
	 		if($fileList) $fileFilter = "\"ID\" IN (" . implode(",", $fileList) . ")";
	 		else $fileFilter = " 1 = 2 ";
	 	}
	 	
	 	if($data['From']) {
	 		$filter .= ($filter?" AND":"") . " \"LastEdited\" >= '$data[From]'";
	 	}
	 	if($data['To']) {
	 		$filter .= ($filter?" AND":"") . " \"LastEdited\" <= '$data[To]'";
	 	}
	 	
	 	if($filter) {
	 		$contentFilter .= ($contentFilter?" AND":"") . $filter;
	 		$fileFilter .= ($fileFilter?" AND":"") . $filter;
	 	}
	 	
	 	if($data['sortby']) {
	 		$sorts = array(
	 			'LastUpdated' => '"LastEdited" DESC',
	 			'PageTitle' => '"Title" ASC',
	 			'Relevance' => '"Relevance" DESC',
	 		);
	 		$sortBy = $sorts[$data['sortby']] ? $sorts[$data['sortby']] : $sorts['Relevance'];
	 	}

		$keywords = $this->addStarsToKeywords($keywords);
	 	
		return $this->searchEngine($keywords, $numPerPage, $sortBy, $contentFilter, true, $fileFilter, $invertedMatch);
	}
	
	function getSearchQuery() {
		$data = $_REQUEST;
	 	if($data['+']) $keywords .= " +" . ereg_replace(" +", " +", trim($data['+']));
	 	if($data['quote']) $keywords .= ' "' . $data['quote'] . '"';
	 	if($data['any']) $keywords .= ' ' . $data['any'];
	 	if($data['-']) $keywords .= " -" . ereg_replace(" +", " -", trim($data['-']));	
	 	
	 	return trim($keywords);
	}
	
}

?>