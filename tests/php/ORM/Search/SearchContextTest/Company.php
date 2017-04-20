<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataObject;

class Company extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Company';

    private static $db = array(
        'Name' => 'Varchar',
        'Industry' => 'Varchar',
        'AnnualProfit' => 'Int'
    );

    private static $summary_fields = array(
        'Industry'
    );

    private static $searchable_fields = array(
        'Name' => 'PartialMatchFilter',
        'Industry' => array(
            'field' => TextareaField::class
        ),
        'AnnualProfit' => array(
            'field' => NumericField::class,
            'filter' => 'PartialMatchFilter',
            'title' => 'The Almighty Annual Profit'
        )
    );
}
