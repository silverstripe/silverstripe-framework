<?php

/**
 * @package sapphire
 * @subpackage model
 */


/**
 * Plug-ins for additional functionality in your DataObjects
 *
 * DataObject decorators add extra functionality to your data objects.
 * @package sapphire
 * @subpackage model
 */
abstract class DataObjectDecorator extends Extension {

	/**
	 * Statics on a {@link DataObject} subclass
	 * which can be decorated onto. This list is
	 * limited for security and performance reasons.
	 *
	 * @var array
	 */
	protected static $decoratable_statics = array(
		'db', 
		'has_one', 
		'indexes', 
		'defaults', 
		'has_many', 
		'many_many', 
		'belongs_many_many', 
		'many_many_extraFields',
		'searchable_fields',
	);
	
	/**
	 * Load the extra database fields defined in extraDBFields.
	 * 
	 * @todo Rename to "loadExtraStaticFields", as it decorates more than database related fields.
	 */
	function loadExtraDBFields() {
		$fields = $this->extraDBFields();
		$className = $this->owner->class;

		if($fields) {
			foreach($fields as $relationType => $fields) {
				if(in_array($relationType, self::$decoratable_statics)) {
					eval("$className::\$$relationType = array_merge((array){$className}::\$$relationType, (array)\$fields);");
					$this->owner->set_stat($relationType, eval("return $className::\$$relationType;"));
				}
				$this->owner->set_uninherited('fieldExists', null); 
			}
		}
	}


	/**
	 * Edit the given query object to support queries for this extension
	 *
	 * @param SQLQuery $query Query to augment.
	 */
	function augmentSQL(SQLQuery &$query) {
	}


	/**
	 * Update the database schema as required by this extension.
	 */
	function augmentDatabase() {
	}


	/**
	 * Define extra database fields
	 *
	 * Return a map where the keys are db, has_one, etc, and the values are
	 * additional fields/relations to be defined.
	 *
	 * @return array Returns a map where the keys are db, has_one, etc, and
	 *               the values are additional fields/relations to be defined.
	 */
	function extraDBFields() {
		return array();
	}


	/**
	 * This function is used to provide modifications to the form in the CMS
	 * by the decorator.
	 * By default, no changes are made - if you want you can overload this
	 * function.
	 *
	 * @param FieldSet $fields The FieldSet to modify.
	 */
	function updateCMSFields(FieldSet &$fields) {
	}
}

?>