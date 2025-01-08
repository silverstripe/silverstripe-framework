<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\Validation\CompositeValidator;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;

class Person extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldDetailFormTest_Person';

    private static $db = [
        'FirstName' => 'Varchar',
        'Surname' => 'Varchar'
    ];

    private static $has_one = [
        'Group' => PeopleGroup::class,
        'PolymorphicGroup' => DataObject::class,
        'MultiRelationalGroup' => [
            'class' => DataObject::class,
            DataObjectSchema::HAS_ONE_MULTI_RELATIONAL => true,
        ],
    ];

    private static $many_many = [
        'Categories' => Category::class,
        'FavouriteGroups' => PeopleGroup::class
    ];

    private static $many_many_extraFields = [
        'Categories' => [
            'IsPublished' => 'Boolean',
            'PublishedBy' => 'Varchar'
        ]
    ];

    private static $default_sort = '"FirstName"';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
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

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();
        $validator->addValidator(new RequiredFieldsValidator(
            [
            'FirstName',
            'Surname'
            ]
        ));
        return $validator;
    }

    public function getCMSEditLink(): ?string
    {
        return sprintf('my-admin/%d', $this->ID);
    }
}
