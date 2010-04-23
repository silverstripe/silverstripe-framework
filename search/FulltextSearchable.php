<?php
/**
 * Provides a simple search engine for your site based on the MySQL FULLTEXT index
 * 
 * @package sapphire
 * @subpackage search
 */
class FulltextSearchable extends DataObjectDecorator {
	protected $searchFields;
	
	/**
	 * Enable the default configuration of MySQL full-text searching on the given data classes.
	 */
	static function enable($searchableClasses = array('SiteTree', 'File')) {
		$defaultColumns = array(
			'SiteTree' => 'Title,MenuTitle,Content,MetaTitle,MetaDescription,MetaKeywords',
			'File' => 'Filename,Title,Content'
		);
		
		if(!is_array($searchableClasses)) $searchableClasses = array($searchableClasses);
		foreach($searchableClasses as $class) {
			if(isset($defaultColumns[$class])) {
				Object::add_extension($class, "FulltextSearchable('{$defaultColumns[$class]}')");
			} else {
				throw new Exception("FulltextSearchable::enable() I don't know the default search columns for class '$class'");
			}
		}
		
		Object::add_extension("ContentController", "ContentControllerSearchExtension");
	}
	
	function __construct($searchFields) {
		if(is_array($searchFields)) $this->searchFields = implode(',', $searchFields);
		else $this->searchFields = $searchFields;
		parent::__construct();
	}
	
	function extraStatics($class = null, $extension = null) {
		if($extension && preg_match('/\([\'"](.*)[\'"]\)/', $extension, $matches)) {
			$searchFields = $matches[1];
		
			return array(
				'indexes' => array(
					"SearchFields" => Array(
						'type'=>'fulltext', 
						'name'=>'SearchFields', 
						'value'=> $searchFields
					),
				)
			);
		}
	}
}