<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Forms\NumericField;
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
 */
class DBForeignKey extends DBInt
{

    /**
     * @var DataObject
     */
    protected $object;

    /**
     * This represents the number of related objects to show in a dropdown before it reverts
     * to a NumericField. If you are tweaking this value, you should also consider constructing
     * your form field manually rather than allowing it to be scaffolded
     *
     * @config
     * @var int
     */
    private static $dropdown_field_threshold = 100;

    private static $index = true;

    private static $default_search_filter_class = 'ExactMatchFilter';

    public function __construct($name, $object = null)
    {
        $this->object = $object;
        parent::__construct($name);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        if (empty($this->object)) {
            return null;
        }
        $relationName = substr($this->name, 0, -2);
        $hasOneClass = DataObject::getSchema()->hasOneComponent(get_class($this->object), $relationName);
        if (empty($hasOneClass)) {
            return null;
        }
        $hasOneSingleton = singleton($hasOneClass);
        if ($hasOneSingleton instanceof File) {
            $field = Injector::inst()->create(FileHandleField::class, $relationName, $title);
            if ($hasOneSingleton instanceof Image) {
                $field->setAllowedFileCategories('image/supported');
            }
            return $field;
        }

        // Build selector / numeric field
        $titleField = $hasOneSingleton->hasField('Title') ? "Title" : "Name";
        $list = DataList::create($hasOneClass);
        // Don't scaffold a dropdown for large tables, as making the list concrete
        // might exceed the available PHP memory in creating too many DataObject instances
        $threshold = self::config()->get('dropdown_field_threshold');
        if ($list->count() < $threshold) {
            $field = new DropdownField($this->name, $title, $list->map('ID', $titleField));
            $field->setEmptyString(' ');
        } else {
            $field = new NumericField($this->name, $title);
            $field->setRightTitle(_t(self::class . '.DROPDOWN_THRESHOLD_FALLBACK_MESSAGE', 'Too many related objects; fallback field in use'));
        }
        return $field;
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($record instanceof DataObject) {
            $this->object = $record;
        }
        parent::setValue($value, $record, $markChanged);
    }
}
