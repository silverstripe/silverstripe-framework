<?php

namespace SilverStripe\ORM\Tests\DBFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestDataObject extends DataObject implements TestOnly
{
    private static $table_name = 'DBFieldTest_TestDataObject';

    private static $db = [
        'Title' => TestDbField::class,
        'MyTestField' => TestDbField::class,
    ];

    public $setFieldCalledCount = 0;

    public function setField($fieldName, $val)
    {
        $this->setFieldCalledCount++;
        return parent::setField($fieldName, $val);
    }

    public function setMyTestField($val)
    {
        return $this->setField('MyTestField', strtolower($val));
    }
}
