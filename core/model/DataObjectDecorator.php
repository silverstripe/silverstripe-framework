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
	 * Keys are the static names, and the values are whether or not the value is an array that should
	 * be merged.
	 *
	 * @var array
	 */
	protected static $decoratable_statics = array(
		'db' => true, 
		'has_one' => true, 
		'indexes' => true, 
		'defaults' => true, 
		'has_many' => true, 
		'many_many' => true, 
		'belongs_many_many' => true, 
		'many_many_extraFields' => true,
		'searchable_fields' => true,
		'api_access' => false,
	);

	
	private static $extra_statics_loaded = array();
	
	function setOwner(Object $owner, $ownerBaseClass = null) {
		if(!($owner instanceof DataObject)) {
			user_error(sprintf(
				"DataObjectDecorator->setOwner(): Trying to decorate an object of class '%s' with '%s', 
				only Dataobject subclasses are supported.",
				get_class($owner), $this->class),
				E_USER_ERROR
			);
			return false;
		}
		parent::setOwner($owner, $ownerBaseClass);
	}

	/**
	 * Load the extra static definitions for the given extension
	 * class name, called by {@link Object::add_extension()}
	 * 
	 * @param string $class Class name of the owner class (or owner base class)
	 * @param string $extension Class name of the extension class
	 */
	public static function load_extra_statics($class, $extension) {
		if(!empty(self::$extra_statics_loaded[$class][$extension])) return;
		self::$extra_statics_loaded[$class][$extension] = true;

		// If the extension has been manually applied to a subclass, we should ignore that.
		if(Object::has_extension(get_parent_class($class), $extension)) return;

		$callable = array($extension, 'extraDBFields');
		if (!is_callable($callable)) $callable = array($extension, 'extraStatics');
		
		$statics = call_user_func($callable);
		
		if($statics) {
			foreach($statics as $name => $newVal) {
				if(isset(self::$decoratable_statics[$name])) {
					$origVal = self::get_static($class, $name);
				
					// Array to be merged 
					if(self::$decoratable_statics[$name]) {
						// Can't use add_static_var() here as it would merge the array rather than replacing
						self::set_static($class, $name, array_merge((array)$origVal, $newVal));
					
						// Value to be overwritten
					} else {
						Object::set_static($class, $name, $newVal);
					}
				}
			}
			
			DataObject::$cache_has_own_table[$class]       = null;
			DataObject::$cache_has_own_table_field[$class] = null;
		}
	}
	
	/**
	 * @deprecated 2.3 Use loadExtraStatics()
	 */
	function loadExtraDBFields() {
		return $this->loadExtraStatics();
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
	
	function onBeforeWrite() {
	}
	
	function onAfterWrite() {
	}
	
	function onBeforeDelete() {
	}
	
	function onAfterDelete() {
	}
	
	function requireDefaultRecords() {
	}

	function populateDefaults() {
	}
	
	function can($member) {
	}
	
	function canEdit($member) {
	}
	
	function canDelete($member) {
	}
	
	function canCreate($member) {
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
	function extraStatics() {
		return array();
	}

	/**
	 * This function is used to provide modifications to the form in the CMS
	 * by the decorator. By default, no changes are made. {@link DataObject->getCMSFields()}.
	 * 
	 * Please consider using {@link updateFormFields()} to globally add
	 * formfields to the record. The method {@link updateCMSFields()}
	 * should just be used to add or modify tabs, or fields which
	 * are specific to the CMS-context.
	 *
	 * Caution: Use {@link FieldSet->addFieldToTab()} to add fields.
	 *
	 * @param FieldSet $fields FieldSet with a contained TabSet
	 */
	function updateCMSFields(FieldSet &$fields) {
	}
	
	/**
	 * This function is used to provide modifications to the form used
	 * for front end forms. {@link DataObject->getFrontEndFields()}
	 * 
	 * Caution: Use {@link FieldSet->push()} to add fields.
	 *
	 * @param FieldSet $fields FieldSet without TabSet nesting
	 */
	function updateFrontEndFields(FieldSet &$fields) {
	}
	
	/**
	 * This is used to provide modifications to the form actions
	 * used in the CMS. {@link DataObject->getCMSActions()}.
	 *
	 * @param FieldSet $actions FieldSet
	 */
	function updateCMSActions(FieldSet &$actions) {
	}
	
	/**
	 * this function is used to provide modifications to the summary fields in CMS
	 * by the decorator
	 * By default, the summaryField() of its owner will merge more fields defined in the decorator's
	 * $extra_fields['summary_fields']
	 */
	function updateSummaryFields(&$fields){
		$extra_fields = $this->extraStatics();
		if(isset($extra_fields['summary_fields'])){
			$summary_fields = $extra_fields['summary_fields'];
			
			// if summary_fields were passed in numeric array,
			// convert to an associative array
			if($summary_fields && array_key_exists(0, $summary_fields)) {
				$summary_fields = array_combine(array_values($summary_fields), array_values($summary_fields));
			}
			if($summary_fields) $fields = array_merge($fields, $summary_fields);
		}
	}
	
	/**
	 * this function is used to provide modifications to the fields labels in CMS
	 * by the decorator
	 * By default, the fieldLabels() of its owner will merge more fields defined in the decorator's
	 * $extra_fields['field_labels']
	 */
	function updateFieldLabels(&$lables){
		$extra_fields = $this->extraStatics();
		if(isset($extra_fields['field_labels'])){
			$field_labels = $extra_fields['field_labels'];
			if($field_labels) $lables = array_merge($lables, $field_labels);
		}
	}
	
	/**
	 * Clear any internal caches.
	 */
	function flushCache() {
	}

}
?>
