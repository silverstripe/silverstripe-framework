<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\CompositeField;


class GridFieldImportButton implements GridField_HTMLProvider
{
    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @var CompositeField
     */
    protected $importFormField;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "after", $importFormField = null)
    {
        $this->targetFragment = $targetFragment;
        $this->importFormField = $importFormField;
    }

    /**
     * Place the export button in a <p> tag below the field
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $button = new GridField_FormAction(
            $gridField,
            'import',
            _t('TableListField.CSVIMPORT', 'Import CSV'),
            'import',
            null
        );
        $button->addExtraClass('btn btn-secondary no-ajax font-icon-upload btn--icon-large action_import');
        $button
            ->setAttribute('data-toggle', "modal")
            ->setAttribute('data-target', "#". $gridField->ID() . ' .grid-field-import');

        $button->setForm($gridField->getForm());
        $extra = null;

        if($this->importFormField) {
            $extra = ($this->importFormField instanceof Form)
                ? $this->importFormField->forTemplate()
                : $this->importFormField->Field();
        }

        return array(
            $this->targetFragment => ($extra) ? '<p class="grid-csv-button">' . $button->Field() . '</p>'.$extra : '',
        );
    }

    /**
     * export is an action button
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return [];
    }
}
