<?php

namespace SilverStripe\ORM\FieldType;

use UploadField;
use DropdownField;
use NumericField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * A special type Int field used for foreign keys in has_one relationships.
 * @uses ImageField
 * @uses SimpleImageField
 * @uses FileIFrameField
 * @uses DropdownField
 *
 * @param string $name
 * @param DataObject $object The object that the foreign key is stored on (should have a relation with $name)
 *
 * @package framework
 * @subpackage orm
 */
class DBForeignKey extends DBInt {

	/**
	 * @var DataObject
	 */
	protected $object;

	private static $default_search_filter_class = 'ExactMatchFilter';

	public function __construct($name, $object = null) {
		$this->object = $object;
		parent::__construct($name);
	}

	public function scaffoldFormField($title = null, $params = null) {
		if(empty($this->object)) {
			return null;
		}
		$relationName = substr($this->name,0,-2);
		$hasOneClass = $this->object->hasOneComponent($relationName);
		if(empty($hasOneClass)) {
			return null;
		}
		$hasOneSingleton = singleton($hasOneClass);
		if($hasOneSingleton instanceof File) {
			$field = new UploadField($relationName, $title);
			if($hasOneSingleton instanceof Image) {
				$field->setAllowedFileCategories('image/supported');
			}
			return $field;
		}

		// Build selector / numeric field
		$titleField = $hasOneSingleton->hasField('Title') ? "Title" : "Name";
		$list = DataList::create($hasOneClass);
		// Don't scaffold a dropdown for large tables, as making the list concrete
		// might exceed the available PHP memory in creating too many DataObject instances
		if($list->count() < 100) {
			$field = new DropdownField($this->name, $title, $list->map('ID', $titleField));
			$field->setEmptyString(' ');
		} else {
			$field = new NumericField($this->name, $title);
		}
		return $field;
	}

	public function setValue($value, $record = null, $markChanged = true) {
		if($record instanceof DataObject) {
			$this->object = $record;
		}
		parent::setValue($value, $record, $markChanged);
	}
}


