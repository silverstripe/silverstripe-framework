<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TimeField;
use SilverStripe\ORM\DataObject;

class Child extends DataObject implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_Child';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Parent' => ParentModel::class,
    ];

    public static bool $includeInOwnTab = true;

    public function scaffoldFormFieldForHasOne(
        string $fieldName,
        ?string $fieldTitle,
        string $relationName,
        DataObject $ownerRecord
    ): FormField {
        // Intentionally return a field that is unlikely to be used by default in the future.
        return DateField::create($fieldName, $fieldTitle);
    }

    public function scaffoldFormFieldForHasMany(
        string $relationName,
        ?string $fieldTitle,
        DataObject $ownerRecord,
        bool &$includeInOwnTab
    ): FormField {
        $includeInOwnTab = static::$includeInOwnTab;
        // Intentionally return a field that is unlikely to be used by default in the future.
        return CurrencyField::create($relationName, $fieldTitle);
    }

    public function scaffoldFormFieldForManyMany(
        string $relationName,
        ?string $fieldTitle,
        DataObject $ownerRecord,
        bool &$includeInOwnTab
    ): FormField {
        $includeInOwnTab = static::$includeInOwnTab;
        // Intentionally return a field that is unlikely to be used by default in the future.
        return TimeField::create($relationName, $fieldTitle);
    }
}
