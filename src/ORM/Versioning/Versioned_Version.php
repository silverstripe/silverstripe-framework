<?php

namespace SilverStripe\ORM\Versioning;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\View\ViewableData;

/**
 * Represents a single version of a record.
 *
 * @see Versioned
 */
class Versioned_Version extends ViewableData
{
	/**
	 * @var array
	 */
	protected $record;

	/**
	 * @var DataObject
	 */
	protected $object;

	/**
	 * Create a new version from a database row
	 *
	 * @param array $record
	 */
	public function __construct($record)
	{
		$this->record = $record;
		$record['ID'] = $record['RecordID'];
		$className = $record['ClassName'];

		$this->object = ClassInfo::exists($className) ? new $className($record) : new DataObject($record);
		$this->failover = $this->object;

		parent::__construct();
	}

	/**
	 * Either 'published' if published, or 'internal' if not.
	 *
	 * @return string
	 */
	public function PublishedClass()
	{
		return $this->record['WasPublished'] ? 'published' : 'internal';
	}

	/**
	 * Author of this DataObject
	 *
	 * @return Member
	 */
	public function Author()
	{
		return Member::get()->byID($this->record['AuthorID']);
	}

	/**
	 * Member object of the person who last published this record
	 *
	 * @return Member
	 */
	public function Publisher()
	{
		if (!$this->record['WasPublished']) {
			return null;
		}

		return Member::get()->byID($this->record['PublisherID']);
	}

	/**
	 * True if this record is published via publish() method
	 *
	 * @return boolean
	 */
	public function Published()
	{
		return !empty($this->record['WasPublished']);
	}

	/**
	 * Traverses to a field referenced by relationships between data objects, returning the value
	 * The path to the related field is specified with dot separated syntax (eg: Parent.Child.Child.FieldName)
	 *
	 * @param $fieldName string
	 * @return string | null - will return null on a missing value
	 */
	public function relField($fieldName)
	{
		$component = $this;

		// We're dealing with relations here so we traverse the dot syntax
		if (strpos($fieldName, '.') !== false) {
			$relations = explode('.', $fieldName);
			$fieldName = array_pop($relations);
			foreach ($relations as $relation) {
				// Inspect $component for element $relation
				if ($component->hasMethod($relation)) {
					// Check nested method
					$component = $component->$relation();
				} elseif ($component instanceof SS_List) {
					// Select adjacent relation from DataList
					$component = $component->relation($relation);
				} elseif ($component instanceof DataObject
					&& ($dbObject = $component->dbObject($relation))
				) {
					// Select db object
					$component = $dbObject;
				} else {
					user_error("$relation is not a relation/field on " . get_class($component), E_USER_ERROR);
				}
			}
		}

		// Bail if the component is null
		if (!$component) {
			return null;
		}
		if ($component->hasMethod($fieldName)) {
			return $component->$fieldName();
		}
		return $component->$fieldName;
	}
}
