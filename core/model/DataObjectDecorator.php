<?php
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
		// Don't apply DB fields if the parent object has this extension too
		if(singleton(get_parent_class($this->owner))->extInstance($this->class)) return;
	
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
	 * Augment a write-record request.
	 *
	 * @param SQLQuery $manipulation Query to augment.
	 */
	function augmentWrite(&$manipulation) {
	}


	/**
	 * Define extra database fields
	 *
	 * Return a map where the keys are db, has_one, etc, and the values are
	 * additional fields/relations to be defined.
	 * 
	 * Note: please ensure that the static variable that you are overloading is explicitly defined on the class that
	 * you are extending.  For example, we have added static $has_one = array() to the Member definition, so that we
	 * can add has_one relationships to Member with decorators.
	 * 
	 * If you forget to do this, db/build won't create the new relation.  Don't blame us, blame PHP! ;-)
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
	
	/**
	 * this function is used to provide modifications to the summary fields in CMS
	 * by the decorator
	 * By default, the summaryField() of its owner will merge more fields defined in the decorator's
	 * $extra_fields['summary_fields']
	 */
	function updateSummaryFields(&$fields){
		$extra_fields = $this->extraDBFields();
		if(isset($extra_fields['summary_fields'])){
			$summary_fields = $extra_fields['summary_fields'];
			if($summary_fields) $fields = array_merge($fields, $summary_fields);
		}
	}
	
	function updateSummaryFieldsExcludeExtra(&$fields){
		$extra_fields = $this->extraDBFields();
		if(isset($extra_fields['summary_fields'])){
			$summary_fields = $extra_fields['summary_fields'];
			if($summary_fields)$fields = array_merge($fields, $summary_fields);
		}
	}
}

?>
