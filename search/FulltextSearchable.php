<?php
/**
 * Provides a simple search engine for your site based on the MySQL FULLTEXT index.
 * Adds the {@link FulltextSearchable} extension to data classes,
 * as well as the {@link ContentControllerSearchExtension} to {@link ContentController}
 * (if the 'cms' module is available as well).
 * (this means you can use $SearchForm in your template without changing your own implementation).
 *
 * CAUTION: Will make all files in your /assets folder searchable by file name
 * unless "File" is excluded from FulltextSearchable::enable().
 *
 * @see http://doc.silverstripe.org/framework/en/tutorials/4-site-search
 *
 * @package framework
 * @subpackage search
 */
class FulltextSearchable extends DataExtension {

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
	 * MyObject::add_extension("FulltextSearchable('MySearchableField,MyOtherField')");
	 * </code>
	 *
	 * Caution: This is a wrapper method that should only be used in _config.php,
	 * and only be called once in your code.
	 *
	 * @param Array $searchableClasses The extension will be applied to all DataObject subclasses
	 *  listed here. Default: {@link SiteTree} and {@link File}.
	 */
	public static function enable($searchableClasses = array('SiteTree', 'File')) {
		$defaultColumns = array(
			'SiteTree' => '"Title","MenuTitle","Content","MetaDescription"',
			'File' => '"Title","Filename","Content"'
		);

		if(!is_array($searchableClasses)) $searchableClasses = array($searchableClasses);
		foreach($searchableClasses as $class) {
			if(!class_exists($class)) continue;

			if(isset($defaultColumns[$class])) {
				Config::inst()->update(
					$class, 'create_table_options', array(MySQLSchemaManager::ID => 'ENGINE=MyISAM')
				);
				$class::add_extension("FulltextSearchable('{$defaultColumns[$class]}')");
			} else {
				throw new Exception(
					"FulltextSearchable::enable() I don't know the default search columns for class '$class'"
				);
			}
		}
		self::$searchable_classes = $searchableClasses;
		if(class_exists("ContentController")){
			ContentController::add_extension("ContentControllerSearchExtension");
		}
	}

	/**
	 * @param Array|String $searchFields Comma-separated list (or array) of database column names
	 *  that can be searched on. Used for generation of the database index defintions.
	 */
	public function __construct($searchFields = array()) {
		if(is_array($searchFields)) $this->searchFields = '"'.implode('","', $searchFields).'"';
		else $this->searchFields = $searchFields;

		parent::__construct();
	}

	public static function get_extra_config($class, $extensionClass, $args) {
		return array(
			'indexes' => array(
				'SearchFields' => array(
					'type' => 'fulltext',
					'name' => 'SearchFields',
					'value' => $args[0]
				)
			)
		);
	}

	/**
	 * Shows all classes that had the {@link FulltextSearchable} extension applied through {@link enable()}.
	 *
	 * @return Array
	 */
	public static function get_searchable_classes() {
		return self::$searchable_classes;
	}

}
