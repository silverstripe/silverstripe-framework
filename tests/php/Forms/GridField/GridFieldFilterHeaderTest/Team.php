<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

class Team extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldFilterHeaderTest_Team';

    private static $summary_fields = [
        'Name' => 'Name',
        'City.Initial' => 'City',
        'Cheerleader.Hat.Colour' => 'Cheerleader Hat'
    ];

    private static $db = [
        'Name' => 'Varchar',
        'City' => 'Varchar'
    ];

    private static $has_one = [
        'Cheerleader' => Cheerleader::class,
        'CheerleadersMom' => Mom::class
    ];

    public function getMySummaryField()
    {
        return 'MY SUMMARY FIELD';
    }

    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);
        $fields->add(new CompositeField([
            new TextField('TestCompositeSingle'),
            new CompositeField([
                new TextField('TestCompositeNested'),
            ])
        ]));
        return $fields;
    }
}
