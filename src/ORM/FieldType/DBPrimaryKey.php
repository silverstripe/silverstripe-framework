<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * A special type Int field used for primary keys.
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

    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    public function requireField()
    {
        $spec = DB::get_schema()->IdColumn(false, $this->getAutoIncrement());
        DB::require_field($this->getTable(), $this->getName(), $spec);
    }

    /**
     * @param string $name
     * @param DataObject $object The object that this is primary key for (should have a relation with $name)
     */
    public function __construct($name, $object = null)
    {
        $this->object = $object;
        parent::__construct($name);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return null;
    }

    public function scaffoldSearchField($title = null)
    {
        parent::scaffoldFormField($title);
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        parent::setValue($value, $record, $markChanged);

        if ($record instanceof DataObject) {
            $this->object = $record;
        }

        return $this;
    }
}
