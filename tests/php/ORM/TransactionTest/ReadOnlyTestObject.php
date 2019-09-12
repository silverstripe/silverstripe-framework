<?php

namespace SilverStripe\ORM\Tests\TransactionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;

class ReadOnlyTestObject extends DataObject implements TestOnly
{
    private static $table_name = 'TransactionTest_ReadOnlyObject';

    private static $db = array(
        'Title' => 'Varchar(255)'
    );

    /**
     * Workaround to force the object can't be written to a db
     *
     * @return bool|\SilverStripe\ORM\ValidationResult
     */
    public function validate()
    {
        $result = ValidationResult::create();
        $result->addError('The DataObject is read-only.');

        return $result;
    }
}
