<?php

namespace SilverStripe\Dev\Tests\CsvBulkLoaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Player extends DataObject implements TestOnly
{
    private static $table_name = 'CsvBulkLoaderTest_Player';

    private static $db = array(
        'FirstName' => 'Varchar(255)',
        'Biography' => 'HTMLText',
        'Birthday' => 'Date',
        'ExternalIdentifier' => 'Varchar(255)', // used for uniqueness checks on passed property
        'IsRegistered' => 'Boolean'
    );

    private static $has_one = array(
        'Team' => Team::class,
        'Contract' => PlayerContract::class
    );

    public function getTeamByTitle($title)
    {
        return DataObject::get_one(
            Team::class,
            array(
            '"CsvBulkLoaderTest_Team"."Title"' => $title
            )
        );
    }

    /**
     * Custom setter for "Birthday" property when passed/imported
     * in different format.
     *
     * @param string $val
     * @param array  $record
     */
    public function setUSBirthday($val, $record = null)
    {
        $this->Birthday = preg_replace('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-90-9]{2,4})/', '\\3-\\1-\\2', $val);
    }
}
