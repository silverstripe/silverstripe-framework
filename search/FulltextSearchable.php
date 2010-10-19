<?php
/**
 * Provides a simple search engine for your site based on the MySQL FULLTEXT index.
 * Adds the {@link FulltextSearchable} extension to data classes,
 * as well as the {@link ContentControllerSearchExtension} to {@link ContentController}.
 * (this means you can use $SearchForm in your template without changing your own implementation).
 * 
 * @see http://doc.silverstripe.org/tutorial:4-site-search
 *
 * @package sapphire
 * @subpackage search
 */
class FulltextSearchable extends DataObjectDecorator {

	/**
	 * @var String Comma-separated list of database column names
	 *  that can be searched on. Used for generation of the database index defintions.
	 */
	protected $searchFields;
	
	/**
	 * @var Array List of class names
	 */
	protected static $searchable_classes;

	/**
	 * Enable the default configuration of MySQL full-text searching on the given data classes.
	 * It can be used to limit the searched classes, but not to add your own classes.
	 * For this purpose, please use {@link Object::add_extension()} directly:
	 * <code>
	 * Object::add_extension('MyObject', "FulltextSearchable('MySearchableField,'MyOtherField')");
	 * </code>
	 * 
	 * Caution: This is a wrapper method that should only be used in _config.php,
	 * and only be called once in your code.
	 * 
	 * @param Array $searchableClasses The extension will be applied to all DataObject subclasses
	 *  listed here. Default: {@link SiteTree} and {@link File}.
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
		self::$searchable_classes = $searchableClasses;

		Object::add_extension("ContentController", "ContentControllerSearchExtension");
	}

	/**
	 * @param Array|String $searchFields Comma-separated list (or array) of database column names
	 *  that can be searched on. Used for generation of the database index defintions.
	 */
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
	
	/**
	 * Shows all classes that had the {@link FulltextSearchable} extension applied through {@link enable()}.
	 * 
	 * @return Array
	 */
	function get_searchable_classes() {
		return self::$searchable_classes;
	}
	
}