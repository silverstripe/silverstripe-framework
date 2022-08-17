<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\RelationList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * This component provides a button for opening the add new form provided by
 * {@link GridFieldDetailForm}.
 *
 * Only returns a button if {@link DataObject->canCreate()} for this record
 * returns true.
 */
class GridFieldAddNewButton extends AbstractGridFieldComponent implements GridField_HTMLProvider
{

    protected $targetFragment;

    protected $buttonName;

    public function setButtonName(string $name): SilverStripe\Forms\GridField\GridFieldAddNewButton
    {
        $this->buttonName = $name;

        return $this;
    }

    public function __construct(string $targetFragment = 'before'): void
    {
        $this->targetFragment = $targetFragment;
    }

    public function getHTMLFragments(SilverStripe\Forms\GridField\GridField $gridField): array
    {
        $singleton = singleton($gridField->getModelClass());
        $context = [];
        if ($gridField->getList() instanceof RelationList) {
            $record = $gridField->getForm()->getRecord();
            if ($record && $record instanceof DataObject) {
                $context['Parent'] = $record;
            }
        }

        if (!$singleton->canCreate(null, $context)) {
            return [];
        }

        if (!$this->buttonName) {
            // provide a default button name, can be changed by calling {@link setButtonName()} on this component
            $objectName = $singleton->i18n_singular_name();
            $this->buttonName = _t('SilverStripe\\Forms\\GridField\\GridField.Add', 'Add {name}', ['name' => $objectName]);
        }

        $data = new ArrayData([
            'NewLink' => Controller::join_links($gridField->Link('item'), 'new'),
            'ButtonName' => $this->buttonName,
        ]);

        $templates = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return [
            $this->targetFragment => $data->renderWith($templates),
        ];
    }
}
