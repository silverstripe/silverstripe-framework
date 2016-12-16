<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataObject;

class Category extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormTest_Category';

    private static $db = array(
        'Name' => 'Varchar'
    );

    private static $belongs_many_many = array(
        'People' => Person::class
    );

    private static $default_sort = '"Name"';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        // TODO No longer necessary once FormScaffolder uses GridField
        $fields->replaceField(
            'People',
            GridField::create(
                'People',
                'People',
                $this->People(),
                GridFieldConfig_RelationEditor::create()
            )
        );
        return $fields;
    }
}
