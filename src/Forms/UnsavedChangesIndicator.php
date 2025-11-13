<?php

namespace SilverStripe\Forms;

use SilverStripe\Forms\FormAction;

/**
 * A visual indicator to show whether a form has unsaved changes.
 *
 * Note that this isn't strictly a FormAction as it doesn't perform any action when clicked.
 * However, extending FormAction allows us to easily integrate with the existing form action
 * architecture (e.g. placement in the form actions area).
 */
class UnsavedChangesIndicator extends FormAction
{
    private static array $minutes = [
        'notice' => 5,
        'warning' => 10,
    ];

    protected $schemaComponent = 'UnsavedChangesIndicatorTimer';

    public function getSchemaDataDefaults()
    {
        // This is used for initialising the React component
        // Refer to silverstrpie/admin UnsavedChangesIndicatorTimer.js
        $data = parent::getSchemaDataDefaults();
        $data['minutes'] = UnsavedChangesIndicator::config()->get('minutes');
        return $data;
    }

    public function getAttributes()
    {
        // This is used for entwine initialisation
        // Refer to silverstrpie/admin UnsavedChangesIndicatorEntwine.js
        $attributes = parent::getAttributes();
        $attributes['data-minutes'] = json_encode(UnsavedChangesIndicator::config()->get('minutes'));
        return $attributes;
    }
}
