<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\View\ViewableData;

/**
 * A special type Int field used for primary keys.
 */
class DBPrimaryKey extends DBInt
{
    protected ?DataObject $object;

    private static $default_search_filter_class = 'ExactMatchFilter';

    protected bool $autoIncrement = true;

    /**
     * @param DataObject $object The object that this is primary key for (should have a relation with $name)
     */
    public function __construct(?string $name, ?DataObject $object = null)
    {
        $this->object = $object;
        parent::__construct($name);
    }

    public function setAutoIncrement(bool $autoIncrement): static
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function getAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function requireField(): void
    {
        $spec = DB::get_schema()->IdColumn(false, $this->getAutoIncrement());
        DB::require_field($this->getTable(), $this->getName(), $spec);
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return null;
    }

    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        return parent::scaffoldFormField($title);
    }

    public function setValue(mixed $value, null|array|ViewableData $record = null, bool $markChanged = true): static
    {
        parent::setValue($value, $record, $markChanged);

        if ($record instanceof DataObject) {
            $this->object = $record;
        }

        return $this;
    }
}
