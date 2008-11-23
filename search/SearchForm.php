<?php
/**
 * Standard basic search form which conducts a fulltext search on all {@link SiteTree}
 * objects. 
 * 
 * @see Use ModelController and SearchContext for a more generic search implementation based around DataObject
 * @package sapphire
 * @subpackage search
 */
class SearchForm extends Form {
	
	/**
	 * @var boolean $showInSearchTurnOn
	 * @deprecated 2.3 SiteTree->ShowInSearch should always be respected
	 */
	protected $showInSearchTurnOn;
	
	/**
	 * @var int $numPerPage How many results are shown per page.
	 * Relies on pagination being implemented in the search results template.
	 */
	protected $numPerPage = 10;
	
	/**
	 * 
	 * @param Controller $controller
	 * @param string $name The name of the form (used in URL addressing)
	 * @param FieldSet $fields Optional, defaults to a single field named "Search". Search logic needs to be customized
	 *  if fields are added to the form.
	 * @param FieldSet $actions Optional, defaults to a single field named "Go".
	 * @param boolean $showInSearchTurnOn DEPRECATED 2.3
	 */
	function __construct($controller, $name, $fields = null, $actions = null, $showInSearchTurnOn = true) {
		$this->showInSearchTurnOn = $showInSearchTurnOn;
		
		if(!$fields) {
			$fields = new FieldSet(
				new TextField('Search', _t('SearchForm.SEARCH', 'Search')
			));
		}
		
		if(!$actions) {
			$actions = new FieldSet(
				new FormAction("getResults", _t('SearchForm.GO', 'Go'))
			);
		}
		
		parent::__construct($controller, $name, $fields, $actions);
		
		$this->setFormMethod('get');
		
		$this->disableSecurityToken();
	}
	
	public function forTemplate() {
		return $this->renderWith(array(
			'SearchForm',
			'Form'
		));
	}

	/**
	 * Return dataObjectSet of the results using $_REQUEST to get info from form.
	 * Wraps around {@link searchEngine()}.
	 * 
	 * @param int $numPerPage DEPRECATED 2.3 Use SearchForm->numPerPage
	 * @param array $data Request data as an associative array. Should contain at least a key 'Search' with all searched keywords.
	 * @return DataObjectSet
	 */
	public function getResults($numPerPage = null, $data = null){
	 	// legacy usage: $data was defaulting to $_REQUEST, parameter not passed in doc.silverstripe.com tutorials
		if(!isset($data)) $data = $_REQUEST;
	
		$keywords = $data['Search'];

	 	$andProcessor = create_function('$matches','
	 		return " +" . $matches[2] . " +" . $matches[4] . " ";
	 	');
	 	$notProcessor = create_function('$matches', '
	 		return " -" . $matches[3];
	 	');

	 	$keywords = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $keywords);
	 	$keywords = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $keywords);
		$keywords = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $keywords);
		$keywords = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $keywords);
		
		$keywords = $this->addStarsToKeywords($keywords);

		if(strpos($keywords, '"') !== false || strpos($keywords, '+') !== false || strpos($keywords, '-') !== false || strpos($keywords, '*') !== false) {
			$results = $this->searchEngine($keywords, $numPerPage, "Relevance DESC", "", true);
		} else {
			$results = $this->searchEngine($keywords, $numPerPage);
		}
		
		// filter by permission
		if($results) foreach($results as $result) {
			if(!$result->canView()) $results->remove($result);
		}
		
		return $results;
	}

	protected function addStarsToKeywords($keywords) {
		if(!trim($keywords)) return "";
		// Add * to each keyword
		$splitWords = split(" +" , trim($keywords));
		while(list($i,$word) = each($splitWords)) {
			if($word[0] == '"') {
				while(list($i,$subword) = each($splitWords)) {
					$word .= ' ' . $subword;
					if(substr($subword,-1) == '"') break;
				}
			} else {
				$word .= '*';
			}
			$newWords[] = $word;
		}
		return implode(" ", $newWords);
	}
	
	
		
		
	/**
	 * The core search engine, used by this class and its subclasses to do fun stuff.
	 * Searches both SiteTree and File.
	 * 
	 * @param string $keywords Keywords as a string.
	 */
	public function searchEngine($keywords, $numPerPage = null, $sortBy = "Relevance DESC", $extraFilter = "", $booleanSearch = false, $alternativeFileFilter = "", $invertedMatch = false) {
		if(!$numPerPage) $numPerPage = $this->numPerPage;
		$fileFilter = '';	 	
	 	$keywords = addslashes($keywords);
	 	
	 	if($booleanSearch) $boolean = "IN BOOLEAN MODE";
	 	if($extraFilter) {
	 		$extraFilter = " AND $extraFilter";
	 		
	 		if($alternativeFileFilter) $fileFilter = " AND $alternativeFileFilter";
	 		else $fileFilter = $extraFilter;
	 	}
	 	
	 	if($this->showInSearchTurnOn)	$extraFilter .= " AND showInSearch <> 0";

		$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
		$limit = $start . ", " . (int) $numPerPage;
		
		$notMatch = $invertedMatch ? "NOT " : "";
		if($keywords) {
			$matchContent = "MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$keywords' $boolean)";
			$matchFile = "MATCH (Filename, Title, Content) AGAINST ('$keywords' $boolean) AND ClassName = 'File'";
	
			// We make the relevance search by converting a boolean mode search into a normal one
			$relevanceKeywords = str_replace(array('*','+','-'),'',$keywords);
			$relevanceContent = "MATCH (Title) AGAINST ('$relevanceKeywords') + MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$relevanceKeywords')";
			$relevanceFile = "MATCH (Filename, Title, Content) AGAINST ('$relevanceKeywords')";
		} else {
			$relevanceContent = $relevanceFile = 1;
			$matchContent = $matchFile = "1 = 1";
		}

		$queryContent = singleton('SiteTree')->extendedSQL($notMatch . $matchContent . $extraFilter, "");

		$baseClass = reset($queryContent->from);
		// There's no need to do all that joining
		$queryContent->from = array(str_replace('`','',$baseClass) => $baseClass);
		$queryContent->select = array("ClassName","$baseClass.ID","ParentID","Title","URLSegment","Content","LastEdited","Created","_utf8'' AS Filename", "_utf8'' AS Name", "$relevanceContent AS Relevance", "CanViewType");
		$queryContent->orderby = null;

		$queryFiles = singleton('File')->extendedSQL($notMatch . $matchFile . $fileFilter, "");
		$baseClass = reset($queryFiles->from);
		// There's no need to do all that joining
		$queryFiles->from = array(str_replace('`','',$baseClass) => $baseClass);
		$queryFiles->select = array("ClassName","$baseClass.ID","_utf8'' AS ParentID","Title","_utf8'' AS URLSegment","Content","LastEdited","Created","Filename","Name","$relevanceFile AS Relevance","NULL AS CanViewType");
		$queryFiles->orderby = null;
		
		$fullQuery = $queryContent->sql() . " UNION " . $queryFiles->sql() . " ORDER BY $sortBy LIMIT $limit";
		$totalCount = $queryContent->unlimitedRowCount() + $queryFiles->unlimitedRowCount();
	
		$records = DB::query($fullQuery);

		foreach($records as $record)
			$objects[] = new $record['ClassName']($record);

		if(isset($objects)) $doSet = new DataObjectSet($objects);
		else $doSet = new DataObjectSet();
		
		$doSet->setPageLimits($start, $numPerPage, $totalCount);
		return $doSet;
	}
	
	/**
	 * Get the search query for display in a "You searched for ..." sentence.
	 * 
	 * @param array $data
	 * @return string
	 */
	public function getSearchQuery($data = null) {
		// legacy usage: $data was defaulting to $_REQUEST, parameter not passed in doc.silverstripe.com tutorials
		if(!isset($data)) $data = $_REQUEST;
		
		return Convert::raw2xml($data['Search']);
	}

}

?>