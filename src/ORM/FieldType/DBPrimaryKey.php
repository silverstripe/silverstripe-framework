<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * A special type Int field used for primary keys.
 *
 * @todo Allow for custom limiting/filtering of scaffoldFormField dropdown
 */
class DBPrimaryKey extends DBInt
{
    /**
     * @var DataObject
     */
    protected $object;

    private static $default_search_filter_class = 'ExactMatchFilter';

    /**
     * @var bool
     */
    protected $autoIncrement = true;

    public function setAutoIncrement(bool $autoIncrement): SilverStripe\ORM\FieldType\DBPrimaryKey
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

    /**
     * @param string $name
     * @param DataObject $object The object that this is primary key for (should have a relation with $name)
     */
    public function __construct(string $name, $object = null): void
    {
        $this->object = $object;
        parent::__construct($name);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return null;
    }

    public function scaffoldSearchField($title = null): void
    {
        parent::scaffoldFormField($title);
    }

    public function setValue(int $value, DNADesign\Elemental\Models\BaseElement $record = null, bool $markChanged = true): void
    {
        parent::setValue($value, $record, $markChanged);

        if ($record instanceof DataObject) {
            $this->object = $record;
        }
    }
}
