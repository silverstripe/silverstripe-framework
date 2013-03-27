<?php
/**
 * An extension that adds additional functionality to a {@link DataObject}.
 *
 * @package framework
 * @subpackage model
 */
abstract class DataExtension extends Extension {

	public static function get_extra_config($class, $extension, $args) {
		if(method_exists($extension, 'extraDBFields')) {
			$extraStaticsMethod = 'extraDBFields';
		} else {
			$extraStaticsMethod = 'extraStatics';
		}

		$statics = Injector::inst()->get($extension, true, $args)->$extraStaticsMethod($class, $extension);

		if ($statics) {
			Deprecation::notice('3.1.0',
				"$extraStaticsMethod deprecated. Just define statics on your extension, or use get_extra_config",
				Deprecation::SCOPE_GLOBAL);
			return $statics;
		}
	}

	public static function unload_extra_statics($class, $extension) {
		throw new Exception('unload_extra_statics gone');
	}

	/**
	 * Hook for extension-specific validation.
	 *
	 * @param $validationResult Local validation result
	 * @throws ValidationException
	 */
	public function validate(ValidationResult $validationResult) {
	}
	
	/**
	 * Edit the given query object to support queries for this extension
	 *
	 * @param SQLQuery $query Query to augment.
	 */
	public function augmentSQL(SQLQuery &$query) {
	}

	/**
	 * Update the database schema as required by this extension.
	 *
	 * When duplicating a table's structure, remember to duplicate the create options
	 * as well. See {@link Versioned->augmentDatabase} for an example.
	 */
	public function augmentDatabase() {
	}
	
	/**
	 * Augment a write-record request.
	 *
	 * @param SQLQuery $manipulation Query to augment.
	 */
	public function augmentWrite(&$manipulation) {
	}
	
	public function onBeforeWrite() {
	}
	
	public function onAfterWrite() {
	}
	
	public function onBeforeDelete() {
	}
	
	public function onAfterDelete() {
	}
	
	public function requireDefaultRecords() {
	}

	public function populateDefaults() {
	}
	
	public function can($member) {
	}
	
	public function canEdit($member) {
	}
	
	public function canDelete($member) {
	}
	
	public function canCreate($member) {
	}

	/**
	 * Define extra database fields
	 *
	 * Return a map where the keys are db, has_one, etc, and the values are
	 * additional fields/relations to be defined.
	 * 
	 * @param $class since this method might be called on the class directly
	 * @param $extension since this can help to extract parameters to help set indexes
	 *
	 * @return array Returns a map where the keys are db, has_one, etc, and
	 *               the values are additional fields/relations to be defined.
	 */
	public function extraStatics($class = null, $extension = null) {
		return array();
	}
	
	/**
	 * This function is used to provide modifications to the form in the CMS
	 * by the extension. By default, no changes are made. {@link DataObject->getCMSFields()}.
	 * 
	 * Please consider using {@link updateFormFields()} to globally add
	 * formfields to the record. The method {@link updateCMSFields()}
	 * should just be used to add or modify tabs, or fields which
	 * are specific to the CMS-context.
	 *
	 * Caution: Use {@link FieldList->addFieldToTab()} to add fields.
	 *
	 * @param FieldList $fields FieldList with a contained TabSet
	 */
	public function updateCMSFields(FieldList $fields) {
	}
	
	/**
	 * This function is used to provide modifications to the form used
	 * for front end forms. {@link DataObject->getFrontEndFields()}
	 * 
	 * Caution: Use {@link FieldList->push()} to add fields.
	 *
	 * @param FieldList $fields FieldList without TabSet nesting
	 */
	public function updateFrontEndFields(FieldList $fields) {
	}
	
	/**
	 * This is used to provide modifications to the form actions
	 * used in the CMS. {@link DataObject->getCMSActions()}.
	 *
	 * @param FieldList $actions FieldList
	 */
	public function updateCMSActions(FieldList $actions) {
	}
	
	/**
	 * this function is used to provide modifications to the summary fields in CMS
	 * by the extension
	 * By default, the summaryField() of its owner will merge more fields defined in the extension's
	 * $extra_fields['summary_fields']
	 *
	 * @param array $fields Array of field names
	 */
	public function updateSummaryFields(&$fields) {
		$summary_fields = Config::inst()->get($this->class, 'summary_fields');
		if($summary_fields) {
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
	 * by the extension
	 * By default, the fieldLabels() of its owner will merge more fields defined in the extension's
	 * $extra_fields['field_labels']
	 *
	 * @param array $labels Array of field labels
	 */
	public function updateFieldLabels(&$labels) {
		$field_labels = Config::inst()->get($this->class, 'field_labels');
		if($field_labels) {
			$labels = array_merge($labels, $field_labels);
		}
	}

}

