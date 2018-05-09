<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * Provides the entry point to editing a single record presented by the
 * {@link GridField}.
 *
 * Doesn't show an edit view on its own or modifies the record, but rather
 * relies on routing conventions established in {@link getColumnContent()}.
 *
 * The default routing applies to the {@link GridFieldDetailForm} component,
 * which has to be added separately to the {@link GridField} configuration.
 */
class GridFieldEditButton implements GridField_ColumnProvider, GridField_ActionProvider, GridField_ActionMenuItem
{
    /**
     * HTML classes to be added to GridField edit buttons
     *
     * @var string[]
     */
    protected $extraClass = [
        'grid-field__icon-action--hidden-on-hover' => true,
        'font-icon-edit' => true,
        'btn--icon-large' => true
    ];

    public function getTitle($gridField, $record)
    {
        return 'Edit';
    }

    public function getGroup($gridField, $record)
    {
        return 'General';
    }

    public function getType($gridField, $record)
    {
        return GridField_ActionMenuItem::LINK;
    }

    public function getUrl($gridField, $record)
    {
        return Controller::join_links($gridField->Link('item'), $record->ID, 'edit');
    }

    public function getExtraData($gridField, $record)
    {
        return [
            "extraClass" => "edit-link"
        ];
    }

    /**
     * Add a column 'Delete'
     *
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::create_tag()
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return array('class' => 'grid-field__col-compact');
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            return array('title' => '');
        }
        return [];
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return array('Actions');
    }

    /**
     * Which GridField actions are this component handling.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return array();
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string The HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        // No permission checks, handled through GridFieldDetailForm,
        // which can make the form readonly if no edit permissions are available.

        $data = new ArrayData(array(
            'Link' => Controller::join_links($gridField->Link('item'), $record->ID, 'edit'),
            'ExtraClass' => $this->getExtraClass()
        ));

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return $data->renderWith($template);
    }

    /**
     * Get the extra HTML classes to add for edit buttons
     *
     * @return string
     */
    public function getExtraClass()
    {
        return implode(' ', array_keys($this->extraClass));
    }

    /**
     * Add an extra HTML class
     *
     * @param string $class
     * @return $this
     */
    public function addExtraClass($class)
    {
        $this->extraClass[$class] = true;

        return $this;
    }

    /**
     * Remove an HTML class
     *
     * @param string $class
     * @return $this
     */
    public function removeExtraClass($class)
    {
        unset($this->extraClass[$class]);

        return $this;
    }

    /**
     * Handle the actions and apply any changes to the GridField.
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     *
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
    }
}
