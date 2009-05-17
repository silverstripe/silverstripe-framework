<?php
/**
 * Standard basic search form which conducts a fulltext search on all {@link SiteTree}
 * objects. 
 *
 * If multilingual content is enabled through the {@link Translatable} extension,
 * only pages the currently set language on the holder for this searchform are found.
 * The language is set through a hidden field in the form, which is prepoluated
 * with {@link Translatable::get_current_locale()} when then form is constructed.
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
	 * @deprecated 2.3 Use {@link $pageLength}.
	 */
	protected $numPerPage;
	
	/**
	 * @var int $pageLength How many results are shown per page.
	 * Relies on pagination being implemented in the search results template.
	 */
	protected $pageLength = 10;
	
	/**
	 * Classes to search
	 */	
	protected $classesToSearch = array("SiteTree", "File");
	
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
		
		if(singleton('SiteTree')->hasExtension('Translatable')) {
			$fields->push(new HiddenField('locale', 'locale', Translatable::get_current_locale()));
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
	 * Set the classes to search.
	 * Currently you can only choose from "SiteTree" and "File", but a future version might improve this. 
 	 */
	function classesToSearch($classes) {
		$illegalClasses = array_diff($classes, array('SiteTree', 'File'));
		if($illegalClasses) {
			user_error("SearchForm::classesToSearch() passed illegal classes '" . implode("', '", $illegalClasses) . "'.  At this stage, only File and SiteTree are allowed", E_USER_WARNING);
		}
		$legalClasses = array_intersect($classes, array('SiteTree', 'File'));		
		$this->classesToSearch = $legalClasses;
	}

	/**
	 * Return dataObjectSet of the results using $_REQUEST to get info from form.
	 * Wraps around {@link searchEngine()}.
	 * 
	 * @param int $pageLength DEPRECATED 2.3 Use SearchForm->pageLength
	 * @param array $data Request data as an associative array. Should contain at least a key 'Search' with all searched keywords.
	 * @return DataObjectSet
	 */
	public function getResults($pageLength = null, $data = null){
	 	// legacy usage: $data was defaulting to $_REQUEST, parameter not passed in doc.silverstripe.com tutorials
		if(!isset($data)) $data = $_REQUEST;
		
		// set language (if present)
		if(singleton('SiteTree')->hasExtension('Translatable') && isset($data['locale'])) {
			$origLocale = Translatable::get_current_locale();
			Translatable::set_current_locale($data['locale']);
		}
	
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
			$results = $this->searchEngine($keywords, $pageLength, "Relevance DESC", "", true);
		} else {
			$results = $this->searchEngine($keywords, $pageLength);
		}
		
		// filter by permission
		if($results) foreach($results as $result) {
			if(!$result->canView()) $results->remove($result);
		}
		
		// reset locale
		if(singleton('SiteTree')->hasExtension('Translatable') && isset($data['locale'])) {
			Translatable::set_current_locale($origLocale);
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
	public function searchEngine($keywords, $pageLength = null, $sortBy = "Relevance DESC", $extraFilter = "", $booleanSearch = false, $alternativeFileFilter = "", $invertedMatch = false) {
		if(!$pageLength) $pageLength = $this->pageLength;
		$fileFilter = '';	 	
	 	$keywords = addslashes($keywords);
	
		$extraFilters = array('SiteTree' => '', 'File' => '');
	 	
	 	if($booleanSearch) $boolean = "IN BOOLEAN MODE";
	 	if($extraFilter) {
	 		$extraFilters['SiteTree'] = " AND $extraFilter";
	 		
	 		if($alternativeFileFilter) $extraFilters['File'] = " AND $alternativeFileFilter";
	 		else $extraFilters['File'] = $extraFilters['SiteTree'];
	 	}
	 	
	 	if($this->showInSearchTurnOn)	$extraFilters['SiteTree'] .= " AND showInSearch <> 0";

		$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
		$limit = $start . ", " . (int) $pageLength;
		
		$notMatch = $invertedMatch ? "NOT " : "";
		if($keywords) {
			$match['SiteTree'] = "MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$keywords' $boolean)";
			$match['File'] = "MATCH (Filename, Title, Content) AGAINST ('$keywords' $boolean) AND ClassName = 'File'";
	
			// We make the relevance search by converting a boolean mode search into a normal one
			$relevanceKeywords = str_replace(array('*','+','-'),'',$keywords);
			$relevance['SiteTree'] = "MATCH (Title) AGAINST ('$relevanceKeywords') + MATCH (Title, MenuTitle, Content, MetaTitle, MetaDescription, MetaKeywords) AGAINST ('$relevanceKeywords')";
			$relevance['File'] = "MATCH (Filename, Title, Content) AGAINST ('$relevanceKeywords')";
		} else {
			$relevance['SiteTree'] = $relevance['File'] = 1;
			$match['SiteTree'] = $match['File'] = "1 = 1";
		}

		// Generate initial queries and base table names
		$baseClasses = array('SiteTree' => '', 'File' => '');
		foreach($this->classesToSearch as $class) {
			$queries[$class] = singleton($class)->extendedSQL($notMatch . $match[$class] . $extraFilters[$class], "");
			$baseClasses[$class] = reset($queries[$class]->from);
		}
		
		// Make column selection lists
		$select = array(
			'SiteTree' => array("ClassName","$baseClasses[SiteTree].ID","ParentID","Title","URLSegment","Content","LastEdited","Created","_utf8'' AS Filename", "_utf8'' AS Name", "$relevance[SiteTree] AS Relevance", "CanViewType"),
			'File' => array("ClassName","$baseClasses[File].ID","_utf8'' AS ParentID","Title","_utf8'' AS URLSegment","Content","LastEdited","Created","Filename","Name","$relevance[File] AS Relevance","NULL AS CanViewType"),
		);
		
		// Process queries
		foreach($this->classesToSearch as $class) {
			// There's no need to do all that joining
			$queries[$class]->from = array(str_replace('`','',$baseClasses[$class]) => $baseClasses[$class]);
			$queries[$class]->select = $select[$class];
			$queries[$class]->orderby = null;
		}

		// Combine queries
		$querySQLs = array();
		$totalCount = 0;
		foreach($queries as $query) {
			$querySQLs[] = $query->sql();
			$totalCount += $query->unlimitedRowCount();
		}
		$fullQuery = implode(" UNION ", $querySQLs) . " ORDER BY $sortBy LIMIT $limit";

		// Get records
		$records = DB::query($fullQuery);

		foreach($records as $record)
			$objects[] = new $record['ClassName']($record);

		if(isset($objects)) $doSet = new DataObjectSet($objects);
		else $doSet = new DataObjectSet();
		
		$doSet->setPageLimits($start, $pageLength, $totalCount);
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
	
	/**
	 * Set the maximum number of records shown on each page.
	 * 
	 * @param int $length
	 */
	public function setPageLength($length) {
		$this->pageLength = $length;
	}
	
	/**
	 * @return int
	 */
	public function getPageLength() {
		// legacy handling for deprecated $numPerPage
		return (isset($this->numPerPage)) ? $this->numPerPage : $this->pageLength;
	}

}

?>