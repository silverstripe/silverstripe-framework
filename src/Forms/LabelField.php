<?php

namespace SilverStripe\Forms;

/**
 * Simple label, to add extra text in your forms.
 *
 * Use a {@link ReadonlyField} if you need to display a label and value.
 */
class LabelField extends DatalessField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_STRUCTURAL;

    protected $schemaComponent = 'LabelField';

    /**
     * @param string $name
     * @param null|string $title
     */
    public function __construct($name, $title = null)
    {
        // legacy handling:
        // $title, $headingLevel...
        $args = func_get_args();

        if (!isset($args[1])) {
            $title = (isset($args[0])) ? $args[0] : null;

            if (isset($args[0])) {
                $title = $args[0];
            }

            // Prefix name to avoid collisions.
            $name = 'LabelField' . $title;
        }

        parent::__construct($name, $title);
    }
}
