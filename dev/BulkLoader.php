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
	 * implementation details). The callback method should be defined on the source class.
	 * 
	 * NOTE: If you're trying to get a unique Member record by a particular field that
	 * isn't Email, you need to ensure that Member is correctly set to the unique field
	 * you want, as it will merge any duplicates during {@link Member::onBeforeWrite()}.
	 * 
	 * {@see Member::set_unique_identifier_field()}.
	 * 
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
	
	/**
	 * @var Boolean $clearBeforeImport Delete ALL records before importing.
	 */
	public $deleteExistingRecords = false;
	
	function __construct($objectClass) {
		$this->objectClass = $objectClass;
		parent::__construct();
	}
	
	/*
	 * Load the given file via {@link self::processAll()} and {@link self::processRecord()}.
	 * Optionally truncates (clear) the table before it imports. 
	 *  
	 * @return BulkLoader_Result See {@link self::processAll()}
	 */
	public function load($filepath) {
		ini_set('max_execution_time', 3600);
		increase_memory_limit_to('512M');
		
		//get all instances of the to be imported data object 
		if ($this->deleteExistingRecords) {
			$q = singleton($this->objectClass)->buildSQL();
			if (!empty($this->objectClass)) {
				$idSelector = $this->objectClass . '."ID"';
			}
			else {
				$idSelector = '"ID"';
			}
			$q->select = array($idSelector);
			$ids = $q->execute()->column('ID');
			foreach ($ids as $id) {
				$obj = DataObject::get_by_id($this->objectClass, $id);
				$obj->delete();
				$obj->destroy();
				unset($obj);
			}
		}
		
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
	 * @return BulkLoader_Result A collection of objects which are either created, updated or deleted.
	 * 'message': free-text string that can optionally provide some more information about what changes have
	 */
	abstract protected function processAll($filepath, $preview = false);
	

	/**
	 * Process a single record from the file.
	 * 
	 * @param array $record An map of the data, keyed by the header field defined in {@link self::$columnMap}
	 * @param array $columnMap
	 * @param $result BulkLoader_Result (passed as reference)
	 * @param boolean $preview
	 */
	abstract protected function processRecord($record, $columnMap, &$result, $preview = false);
	
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
	 * <code>
	 * array(
	 *   'fields' => array('myFieldName'=>'myDescription'), 
	 *   'relations' => array('myRelationName'=>'myDescription'), 
	 * )
	 * </code>
	 *
	 * @todo Mix in custom column mappings
	 *
	 * @return array
	 **/
	public function getImportSpec() {
		$spec = array();

		// get database columns (fieldlabels include fieldname as a key)
		// using $$includerelations flag as false, so that it only contain $db fields
		$spec['fields'] = (array)singleton($this->objectClass)->fieldLabels(false);
		
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
		return (empty($val) && $val !== '0');
	}
	
}

/**
 * Encapsulates the result of a {@link BulkLoader} import
 * (usually through the {@link BulkLoader->processAll()} method).
 * 
 * @todo Refactor to support lazy-loaded DataObjectSets once they are implemented.
 *
 * @package cms
 * @subpackage bulkloading
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 */
class BulkLoader_Result extends Object {
	
	/**
	 * @var array Stores a map of ID and ClassNames
	 * which can be reconstructed to DataObjects.
	 * As imports can get large we just store enough
	 * information to reconstruct the objects on demand.
	 * Optionally includes a status message specific to
	 * the import of this object. This information is stored
	 * in a custom object property "_BulkLoaderMessage".
	 *
	 * Example:
	 * <code>
	 * array(array('ID'=>1, 'ClassName'=>'Member', 'Message'=>'Updated existing record based on ParentID relation'))
	 * </code>
	 */   
	protected $created = array();
   
	/**
	 * @var array (see {@link $created})
	 */
	protected $updated = array();
   
	/**
	 * @var array (see {@link $created})
	 */
	protected $deleted = array();
	
	/**
	 * Stores the last change.
	 * It is in the same format as {@link $created} but with an additional key, "ChangeType", which will be set to
	 * one of 3 strings: "created", "updated", or "deleted"
	 */
	protected $lastChange = array();
   
	/**
	 * Returns the count of all objects which were
	 * created or updated.
	 *
	 * @return int
	 */
	public function Count() {
		return count($this->created) + count($this->updated);
	}
	
	/**
	 * @return int
	 */
	public function CreatedCount() {
		return count($this->created);
	}
	
	/**
	 * @return int
	 */
	public function UpdatedCount() {
		return count($this->updated);
	}
	
	/**
	 * @return int
	 */
	public function DeletedCount() {
		return count($this->deleted);
	}
	
	/**
	 * Returns all created objects. Each object might
	 * contain specific importer feedback in the "_BulkLoaderMessage" property.
	 *
	 * @return DataObjectSet
	 */
	public function Created() {
		return $this->mapToDataObjectSet($this->created);
	}
	
	/**
	 * @return DataObjectSet
	 */
	public function Updated() {
		return $this->mapToDataObjectSet($this->updated);
	}
	
	/**
	 * @return DataObjectSet
	 */
	public function Deleted() {
		return $this->mapToDataObjectSet($this->deleted);
	}
	
	/**
	 * Returns the last change.
	 * It is in the same format as {@link $created} but with an additional key, "ChangeType", which will be set to
	 * one of 3 strings: "created", "updated", or "deleted"
	 */
	public function LastChange() {
		return $this->lastChange;
	}
	
	/**
	 * @param $obj DataObject
	 * @param $message string
	 */
	public function addCreated($obj, $message = null) {
		$this->created[] = $this->lastChange = array(
			'ID' => $obj->ID,
			'ClassName' => $obj->class,
			'Message' => $message
		);
		$this->lastChange['ChangeType'] = 'created';
	}
	
	/**
	 * @param $obj DataObject
	 * @param $message string
	 */
	public function addUpdated($obj, $message = null) {
		$this->updated[] = $this->lastChange = array(
			'ID' => $obj->ID,
			'ClassName' => $obj->class,
			'Message' => $message
		);
		$this->lastChange['ChangeType'] = 'updated';
	}
	
	/**
	 * @param $obj DataObject
	 * @param $message string
	 */
	public function addDeleted($obj, $message = null) {
		$this->deleted[] = $this->lastChange = array(
			'ID' => $obj->ID,
			'ClassName' => $obj->class,
			'Message' => $message
		);
		$this->lastChange['ChangeType'] = 'deleted';
	}
	
	/**
	 * @param $arr Array containing ID and ClassName maps
	 * @return DataObjectSet
	 */
	protected function mapToDataObjectSet($arr) {
		$set = new DataObjectSet();
		foreach($arr as $arrItem) {
			$obj = DataObject::get_by_id($arrItem['ClassName'], $arrItem['ID']);
			$obj->_BulkLoaderMessage = $arrItem['Message'];
			if($obj) $set->push($obj);
		}
		
		return $set;
	}
   
}
