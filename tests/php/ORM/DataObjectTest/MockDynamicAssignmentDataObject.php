<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * This is a fake DB field specifically design to test dynamic value assignment
 * @property boolean $StaticScalarOnlyField
 * @property boolean $DynamicScalarOnlyField
 * @property boolean $DynamicField
 * @method ManyManyList MockManyMany
 */
class MockDynamicAssignmentDataObject extends DataObject implements TestOnly
{

    private static $table_name = 'MockDynamicAssignmentDataObject';

    private static $db = [
        // This field only emits scalar value and will save
        'StaticScalarOnlyField' => MockDynamicAssignmentDBField::class . '(1,0)',

        // This field tries to emit dynamic assignment but will fail because of scalar only
        'DynamicScalarOnlyField' => MockDynamicAssignmentDBField::class . '(1,1)',

        // This field does dynamic assignement and will pass
        'DynamicField' => MockDynamicAssignmentDBField::class . '(0,1)',
    ];

    private static $many_many = [
        'MockManyMany' => self::class,
    ];

    private static $belongs_many_many = [
        'MockBelongsManyMany' => self::class,
    ];

    private static $many_many_extraFields = [
        'MockManyMany' => [
            // This field only emits scalar value and will save
            'ManyManyStaticScalarOnlyField' => MockDynamicAssignmentDBField::class . '(1,0)',

            // This field tries to emit dynamic assignment but will fail because of scalar only
            'ManyManyDynamicScalarOnlyField' => MockDynamicAssignmentDBField::class . '(1,1)',

            // This field does dynamic assignement and will pass
            'ManyManyDynamicField' => MockDynamicAssignmentDBField::class . '(0,1)',
        ]
    ];
}
