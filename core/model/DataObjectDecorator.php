<?php

/**
 * @package sapphire
 * @subpackage core
 */

/**
 * Plug-ins for additional functionality in your DataObjects.
 * DataObject decorators add extra functionality to your data objects.
 */
abstract class DataObjectDecorator extends Object {
	/**
	 * The DataObject that owns this decorator.
	 * @var DataObject
	 */
	protected $owner;
	
	/**
	 * Set the owner of this decorator.
	 * @param DataObject $owner
	 */
	function setOwner(DataObject $owner) {
		$this->owner = $owner;
	}
	
	/**
	 * Load the extra database fields defined in extraDBFields.
	 */
	function loadExtraDBFields() {
		$fields = $this->extraDBFields();
		$className = $this->owner->class;
		if($fields) {
			foreach($fields as $relationType => $fields) {
				if(in_array($relationType, array('db','has_one','many_many','belongs_many_many','many_many_extraFields'))) {
					eval("$className::\$$relationType = array_merge((array){$className}::\$$relationType, (array)\$fields);");
				}
			}
		}
	}

	/**
	 * Edit the given query object to support queries for this extension.
	 * @param SQLQuery $query Query to augment.
	 */
	abstract function augmentSQL(SQLQuery &$query);
	
	/**
	 * Update the database schema as required by this extension.
	 */
	abstract function augmentDatabase();
	
	/**
	 * Define extra database fields.
	 * Return an map where the keys are db, has_one, etc, and the values are additional fields / relations to be defined.
	 */
	function extraDBFields() {
		return array();
	}

	/**
	 * This function is used to provide modifications to the form in the CMS by the
	 * decorator.  By default, no changes are made - if you want you can overload 
	 * this function.
	 */
	function updateCMSFields(FieldSet &$fields) {
	}
}

?>