<?php

namespace SilverStripe\Forms\GridField;

use RuntimeException;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\SudoModePasswordField;
use SilverStripe\Model\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * GridFieldSudoMode warning that the managed data is protected by SudoMode which will
 * need to be activated before the data can be edited.
 *
 * This is particularly important for GridFields that contain no data, which provide
 * no other way to activate sudo mode.
 */
class GridFieldSudoMode extends AbstractGridFieldComponent implements GridField_HTMLProvider
{
    private string $sectionTitle;

    private int $columnCount;

    public function __construct(string $sectionTile, int $columnCount)
    {
        $this->sectionTitle = $sectionTile;
        $this->columnCount = $columnCount;
    }

    public function getHTMLFragments($gridField)
    {
        if (!$gridField->isReadonly()) {
            throw new RuntimeException('GridFieldSudoMode component can only be used on readonly GridFields');
        }
        $form = $gridField->getForm();
        // Do not render this component if the entire form is protected by sudo mode.
        // If the form is protected then there will already be a SudoModePasswordField as the first form field
        if ($form->getFormRequiresSudoMode()) {
            return [];
        }
        $field = SudoModePasswordField::create();
        $field->setInitiallyCollapsed(true);
        $field->setForGridField(true);
        $field->setSectionTitle($this->sectionTitle);
        $templateData = ArrayData::create([
            'ColumnCount' => $this->columnCount,
            'SudoPasswordField' => $field->FieldHolder(),
        ]);
        $template = SSViewer::get_templates_by_class($this, baseClass: __CLASS__);
        return [
            'header' => $templateData->renderWith($template),
        ];
    }
}
