<?php

/**
 * This is a fake DB field specifically design to test dynamic value assignment
 * @property boolean $StaticScalarOnlyField
 * @property boolean $DynamicScalarOnlyField
 * @property boolean $DynamicField
 * @method ManyManyList MockManyMany
 */
class MockDynamicAssignmentDataObject extends DataObject implements TestOnly
{

    private static $db = array(
        // This field only emits scalar value and will save
        'StaticScalarOnlyField' => 'MockDynamicAssignmentDBField(1,0)',

        // This field tries to emit dynamic assignment but will fail because of scalar only
        'DynamicScalarOnlyField' => 'MockDynamicAssignmentDBField(1,1)',

        // This field does dynamic assignment and will pass
        'DynamicField' => 'MockDynamicAssignmentDBField(0,1)',
    );

    private static $many_many = array(
        "MockManyMany" => 'MockDynamicAssignmentDataObject'
    );

    private static $belongs_many_many = array(
        "MockBelongsManyMany" => 'MockDynamicAssignmentDataObject'
    );

    private static $many_many_extraFields = array(
        'MockManyMany' => array(
            // This field only emits scalar value and will save
            'ManyManyStaticScalarOnlyField' => 'MockDynamicAssignmentDBField(1,0)',

            // This field tries to emit dynamic assignment but will fail because of scalar only
            'ManyManyDynamicScalarOnlyField' => 'MockDynamicAssignmentDBField(1,1)',

            // This field does dynamic assignment and will pass
            'ManyManyDynamicField' => 'MockDynamicAssignmentDBField(0,1)',
        )
    );
}
