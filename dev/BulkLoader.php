<?php
/**
 * A base for bulk loaders of content into the SilverStripe database.
 * Bulk loaders give SilverStripe authors the ability to do large-scale uploads into their Sapphire databases.
 * 
 * You can configure column-handling, 
 * 
 * @todo Add support for adding/editing has_many relations.
 * @todo Add support for deep chaining of relation properties (e.g. Player.Team.Stats.GoalCount)
 * @todo Character conversion
 * 
 * @see http://rfc.net/rfc4180.html
 * @package cms
 * @subpackage bulkloading
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 */
abstract class BulkLoader extends ViewableData {
	
	/**
	 * Each row in the imported dataset should map to one instance
	 * of this class (with optional property translation
	 * through {@self::$columnMaps}.
	 *
	 * @var string
	 */
	public $objectClass;
	
	/**
	 * Override this on subclasses to give the specific functions names.
	 * 
	 * @var string
	 */
	public static $title;

	/**
	 * Map columns to DataObject-properties.
	 * If not specified, we assume the first row
	 * in the file contains the column headers.
	 * The order of your array should match the column order.
	 * 
	 * The column count should match the count of array elements,
	 * fill with NULL values if you want to skip certain columns.
	 *
	 * You can also combine {@link $hasHeaderRow} = true and {@link $columnMap}
	 * and omit the NULL values in your map.
	 * 
	 * Supports one-level chaining of has_one relations and properties with dot notation
	 * (e.g. Team.Title). The first part has to match a has_one relation name
	 * (not necessarily the classname of the used relation).
	 * 
	 * <code>
	 * <?php
	 * 	// simple example
	 *  array(
	 *  	'Title',
	 * 		'Birthday'
	 * 	)
	 * 
	 * // complex example
	 * 	array(
	 * 		'first name' => 'FirstName', // custom column name
	 * 		null, // ignored column
	 * 		'RegionID', // direct has_one/has_many ID setting
	 * 		'OrganisationTitle', // create has_one relation to existing record using $relationCallbacks
	 * 		'street' => 'Organisation.StreetName', // match an existing has_one or create one and write property.
	 * 	);
	 * ?>
	 * </code>
	 *
	 * @var array
	 */
	public $columnMap = array();
	
	/**
	 * Find a has_one relation based on a specific column value.
	 * 
	 * <code>
	 * <?php
	 * array(
	 * 		'OrganisationTitle' => array(
	 * 			'relationname' => 'Organisation', // relation accessor name
	 * 			'callback' => 'getOrganisationByTitle',
	 *		);
	 * );
	 * ?>
	 * </code>
	 *
	 * @var array
	 */
	public $relationCallbacks = array();
	
	/**
	 * Specifies how to determine duplicates based on one or more provided fields
	 * in the imported data, matching to properties on the used {@link DataObject} class.
	 * Alternatively the array values can contain a callback method (see example for
	 * implementation details).
	 * If multiple checks are specified, the first one "wins".
	 * 
	 *  <code>
	 * <?php
	 * array(
	 * 		'customernumber' => 'ID',
	 * 		'phonenumber' => array(
	 * 			'callback' => 'getByImportedPhoneNumber'
	 * 		)
	 * );
	 * ?>
	 * </code>
	 *
	 * @var array
	 */
	public $duplicateChecks = array();
	
	function __construct($objectClass) {
		$this->objectClass = $objectClass;
		
		ini_set('max_execution_time', 3600);
		ini_set('memory_limit', '512M');
	}
	
	/*
	 * Load the given file via {@link self::processAll()} and {@link self::processRecord()}.
	 *  
	 * @return array See {@link self::processAll()}
	 */
	public function load($filepath) {
		return $this->processAll($filepath);
	}
	
	/**
	 * Preview a file import (don't write anything to the database).
	 * Useful to analyze the input and give the users a chance to influence
	 * it through a UI.
	 *
	 * @todo Implement preview()
	 *
	 * @param string $filepath Absolute path to the file we're importing
	 * @return array See {@link self::processAll()}
	 */
	public function preview($filepath) {
		user_error("BulkLoader::preview(): Not implemented", E_USER_ERROR);
	}
	
	/**
	 * Process every record in the file
	 * 
	 * @param string $filepath Absolute path to the file we're importing (with UTF8 content)
	 * @param boolean $preview If true, we'll just output a summary of changes but not actually do anything
	 * @return int Number of affected records
	 * It used to return this, but it was never used and memory inefficient. array Information about the import process, with each row matching a created or updated DataObject.
	 * 	Array structure:
	 *  - 'id': Database id of the created or updated record
	 *  - 'action': Performed action ('create', 'update') 
	 *  - 'message': free-text string that can optionally provide some more information about what changes have
	 */
	abstract protected function processAll($filepath, $preview = false);
	

	/**
	 * Process a single record from the file.
	 * 
	 * @param array $record An map of the data, keyed by the header field defined in {@link self::$columnMap}
	 * @param array $columnMap
	 * @param boolean $preview
	 * @return ArrayData @see self::processAll()
	 */
	abstract protected function processRecord($record, $columnMap, $preview = false);
	
	/**
	 * Return a FieldSet containing all the options for this form; this
	 * doesn't include the actual upload field itself
	 */
	public function getOptionFields() {}
	
	/**
	 * Return a human-readable name for this object.
	 * It defaults to the class name can be overridden by setting the static variable $title
	 * 
	 * @return string
	 */
	public function Title() {
		return ($title = $this->stat('title')) ? $title : $this->class;
	}
	
	/**
	 * Get a specification of all available columns and relations on the used model.
	 * Useful for generation of spec documents for technical end users.
	 * 
	 * Return Format:
	 * <example>
	 * array(
	 *   'fields' => array('myFieldName'=>'myDescription'), 
	 *   'relations' => array('myRelationName'=>'myDescription'), 
	 * )
	 * </example>
	 *
	 * @todo Mix in custom column mappings
	 * @usedby {@link ModelAdmin}
	 *
	 * @return array
	 **/
	public function getImportSpec() {
		$spec = array();

		// get database columns (fieldlabels include fieldname as a key)
		$spec['fields'] = (array)singleton($this->objectClass)->fieldLabels();
		
		$has_ones = singleton($this->objectClass)->has_one();
		$has_manys = singleton($this->objectClass)->has_many();
		$many_manys = singleton($this->objectClass)->many_many();
		
		$spec['relations'] = (array)$has_ones + (array)$has_manys + (array)$many_manys;
		
		return $spec;
	}
	
	/**
	 * Determines if a specific field is null.
	 * Can be useful for unusual "empty" flags in the file,
	 * e.g. a "(not set)" value.
	 * The usual {@link DBField::isNull()} checks apply when writing the {@link DataObject},
	 * so this is mainly a customization method.
	 *
	 * @param mixed $val
	 * @param string $field Name of the field as specified in the array-values for {@link self::$columnMap}.
	 * @return boolean
	 */
	protected function isNullValue($val, $fieldName = null) {
		return (empty($val));
	}
	
}
?>