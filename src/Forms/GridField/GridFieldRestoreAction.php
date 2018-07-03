<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridFieldFooter;
use SilverStripe\Forms\RestoreAction;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\Versioned;

/**
 * This class is a {@link GridField} component that adds a restore action for
 * versioned objects.
 */
class GridFieldRestoreAction implements GridField_ColumnProvider, GridField_ActionProvider, GridField_ActionMenuItem
{
    /**
     * @inheritdoc
     */
    public function getTitle($gridField, $record, $columnName)
    {
        return _t(__CLASS__ . '.RESTORE', "Restore draft");
    }

    /**
     * @inheritdoc
     */
    public function getGroup($gridField, $record, $columnName)
    {
        $field = $this->getRestoreAction($gridField, $record, $columnName);

        return $field ? GridField_ActionMenuItem::DEFAULT_GROUP: null;
    }

    /**
     * @inheritdoc
     */
    public function getExtraData($gridField, $record, $columnName)
    {
        $field = $this->getRestoreAction($gridField, $record, $columnName);

        return $field ? $field->getAttributes() : [];
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
        return ['class' => 'grid-field__col-compact'];
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
            return ['title' => ''];
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
        return ['Actions'];
    }

    /**
     * Which GridField actions are this component handling.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return ['restore'];
    }

    /**
     * Creates a restore action if the action is able to be preformed
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return GridField_FormAction|null
     */
    public function getRestoreAction($gridField, $record, $columnName)
    {
        $isOnDraft = $record->isOnDraft();
        $isPublished = $record->isPublished();
        $canEdit = $record->canEdit();

        if ($canEdit && !$isOnDraft && !$isPublished) {
            $restoreToRoot = RestoreAction::shouldRestoreToRoot($record);

            $title = $restoreToRoot
                ? _t('SilverStripe\\Admin\\ArchiveAdmin.RESTORE_TO_ROOT', 'Restore draft at top level')
                : _t('SilverStripe\\Admin\\ArchiveAdmin.RESTORE', 'Restore draft');
            $description = $restoreToRoot
                ? _t('SilverStripe\\Admin\\ArchiveAdmin.RESTORE_TO_ROOT_DESC', 'Restore the archived version to draft as a top level item')
                : _t('SilverStripe\\Admin\\ArchiveAdmin.RESTORE_DESC', 'Restore the archived version to draft');

            $field = GridField_FormAction::create(
                $gridField,
                'Restore' . $record->ID,
                false,
                "restore",
                ['RecordID' => $record->ID]
            )
                ->addExtraClass('btn btn--no-text btn--icon-md font-icon-back-in-time grid-field__icon-action action-menu--handled action-restore')
                ->setAttribute('classNames', 'font-icon-back-in-time action-restore')
                ->setAttribute('data-to-root', $restoreToRoot)
                ->setDescription($description)
                ->setAttribute('aria-label', $title);
        }

        return isset($field) ? $field : null;
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string The HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $field = $this->getRestoreAction($gridField, $record, $columnName);

        return $field ? $field->Field() : null;
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
        if ($actionName == 'restore') {
            /** @var DataObject $item */
            $item = $gridField->getList()->byID($arguments['RecordID']);
            if (!$item) {
                return;
            }

            $message = RestoreAction::restore($item);

            // If this is handled in a form context then show a message
            if ($message && $controller = $gridField->form->controller) {
                $controller->getResponse()->addHeader('X-Message-Text', $message['text']);
                $controller->getResponse()->addHeader('X-Message-Type', $message['type']);
            }

            $gridField->getList()->remove($item);
        }
    }
}
