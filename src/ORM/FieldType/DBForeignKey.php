<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FileHandleField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\SearchableDropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

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
    protected ?DataObject $object;

    /**
     * Number of related objects to show in a scaffolded searchable dropdown field before it
     * switches to using lazyloading.
     * This will also be used as the lazy load limit
     */
    private static int $dropdown_field_threshold = 100;

    private static string|bool $index = true;

    private static string $default_search_filter_class = 'ExactMatchFilter';

    public function __construct(?string $name, ?DataObject $object = null)
    {
        $this->object = $object;
        parent::__construct($name);
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
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
        $field = $hasOneSingleton->scaffoldFormFieldForHasOne($this->name, $title, $relationName, $this->object);
        return $field;
    }

    public function setValue(mixed $value, null|array|ViewableData $record = null, bool $markChanged = true): static
    {
        if ($record instanceof DataObject) {
            $this->object = $record;
        }
        return parent::setValue($value, $record, $markChanged);
    }
}
