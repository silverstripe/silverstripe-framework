<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Forms\SearchableDropdownField;
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
     * Number of related objects to show in a dropdown before it switches to using lazyloading
     * This will also be used as the lazy load limit
     *
     * @config
     * @var int
     */
    private static $dropdown_field_threshold = 100;

    private static $index = true;

    private static $default_search_filter_class = 'ExactMatchFilter';

    /**
     * Cache for multiple subsequent calls to scaffold form fields with the same foreign key object
     *
     * @var array
     * @deprecated 5.2.0 Will be removed without equivalent functionality to replace it
     */
    protected static $foreignListCache = [];

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
        $relationName = substr($this->name ?? '', 0, -2);
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
            if ($field->hasMethod('setAllowedMaxFileNumber')) {
                $field->setAllowedMaxFileNumber(1);
            }
            return $field;
        }
        $labelField = $hasOneSingleton->hasField('Title') ? 'Title' : 'Name';
        $list = DataList::create($hasOneClass);
        $threshold = static::config()->get('dropdown_field_threshold');
        $overThreshold = $list->count() > $threshold;
        $field = SearchableDropdownField::create($this->name, $title, $list, null, $labelField)
            ->setIsLazyLoaded($overThreshold)
            ->setLazyLoadLimit($threshold);
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
