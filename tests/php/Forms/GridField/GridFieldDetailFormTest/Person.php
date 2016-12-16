<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;

class Person extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormTest_Person';

    private static $db = array(
        'FirstName' => 'Varchar',
        'Surname' => 'Varchar'
    );

    private static $has_one = array(
        'Group' => PeopleGroup::class
    );

    private static $many_many = array(
        'Categories' => Category::class,
        'FavouriteGroups' => PeopleGroup::class
    );

    private static $many_many_extraFields = array(
        'Categories' => array(
            'IsPublished' => 'Boolean',
            'PublishedBy' => 'Varchar'
        )
    );

    private static $default_sort = '"FirstName"';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        // TODO No longer necessary once FormScaffolder uses GridField
        $fields->replaceField(
            'Categories',
            GridField::create(
                'Categories',
                'Categories',
                $this->Categories(),
                GridFieldConfig_RelationEditor::create()
            )
        );
        $fields->replaceField(
            'FavouriteGroups',
            GridField::create(
                'FavouriteGroups',
                'Favourite Groups',
                $this->FavouriteGroups(),
                GridFieldConfig_RelationEditor::create()
            )
        );
        return $fields;
    }

    public function getCMSValidator()
    {
        return new RequiredFields(
            array(
            'FirstName',
            'Surname'
            )
        );
    }
}
