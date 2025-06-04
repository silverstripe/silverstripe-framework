<?php

namespace SilverStripe\Forms;

use SilverStripe\Dev\Deprecation;

/**
 * Readonly field equivalent for literal HTML
 *
 * Unlike HTMLEditorField_Readonly, does not process shortcodes
 */
class HTMLReadonlyField extends ReadonlyField
{
    private static $casting = [
        'Value' => 'HTMLFragment',
        'ValueEntities' => 'HTMLFragment',
    ];

    protected $schemaDataType = HTMLReadonlyField::SCHEMA_DATA_TYPE_STRUCTURAL;

    /**
     * @var string
     */
    protected $schemaComponent = 'HtmlReadonlyField';

    public function Field($properties = [])
    {
        return $this->renderWith($this->getTemplates());
    }

    /**
     * Return value with all values encoded in html entities
     *
     * @return string Raw HTML
     * @deprecated 5.4.0 Will be replaced by getFormattedValueEntities() in a future major release
     */
    public function ValueEntities()
    {
        Deprecation::noticeWithNoReplacment('5.4.0', 'Will be replaced by getFormattedValueEntities() in a future major release');
        return htmlentities($this->Value() ?? '', ENT_COMPAT, 'UTF-8');
    }
}
