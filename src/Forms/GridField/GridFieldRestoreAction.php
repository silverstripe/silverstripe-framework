<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\Versioned;
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
class GridFieldRestoreAction implements GridField_ColumnProvider, GridField_ActionProvider, GridField_ActionMenuItem
{
    /**
     * @inheritdoc
     */
    public function getTitle($gridField, $record, $columnName)
    {
        return _t(__CLASS__ . '.RESTORE', "Restore");
    }

    /**
     * @inheritdoc
     */
    public function getGroup($gridField, $record, $columnName)
    {
        return GridField_ActionMenuItem::DEFAULT_GROUP;
    }

    /**
     * @inheritdoc
     */
    public function getExtraData($gridField, $record, $columnName)
    {
        $field = $this->getRestoreAction($gridField, $record, $columnName);

        if ($field) {
            return $field->getAttributes();
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getUrl($gridField, $record, $columnName)
    {
        return Controller::join_links($gridField->Link('item'), $record->ID, 'edit');
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
    public function getRestoreAction($gridField, $record, $columnName) {

        $isOnDraft = $record->isOnDraft();
        $isPublished = $record->isPublished();
        $canEdit = $record->canEdit();

        if ($canEdit && !$isOnDraft && !$isPublished) {
            // Determine if we should force a restore to root (where once it was a subpage)

            $parentPage = Versioned::get_latest_version($record->classname, $record->ParentID);
            $restoreToRoot = (!$parentPage || !$parentPage->isOnDraft());

            // "restore"
            $title = $restoreToRoot
                ? _t('SilverStripe\\CMS\\Controllers\\CMSMain.RESTORE_TO_ROOT', 'Restore draft at top level')
                : _t('SilverStripe\\CMS\\Controllers\\CMSMain.RESTORE', 'Restore draft');
            $description = $restoreToRoot
                ? _t('SilverStripe\\CMS\\Controllers\\CMSMain.RESTORE_TO_ROOT_DESC', 'Restore the archived version to draft as a top level page')
                : _t('SilverStripe\\CMS\\Controllers\\CMSMain.RESTORE_DESC', 'Restore the archived version to draft');


            $field = GridField_FormAction::create(
                $gridField,
                'Restore' . $record->ID,
                false,
                "restore",
                ['RecordID' => $record->ID]
            )
                ->addExtraClass('btn btn--no-text btn--icon-md font-icon-back-in-time grid-field__icon-action action-menu--handled')
                ->setAttribute('classNames', 'font-icon-back-in-time')
                ->setAttribute('data-to-root', $restoreToRoot)
                ->setDescription($description)
                ->setAttribute('aria-label', $title);
        }

        return $field ?: null;
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
        /** @var DataObject $item */
        $item = $gridField->getList()->byID($arguments['RecordID']);
        if (!$item) {
            return;
        }

        $id = (int)$arguments['RecordID'];
        /** @var SiteTree $restoredPage */
        $restoredItem = Versioned::get_latest_version($item->classname, $id);
        if (!$restoredItem) {
            return new ValidationException($item->classname . " #$id not found", 400);
        }

        if (method_exists($restoredItem, 'doRestoreToStage')) {
            $restoredItem = $restoredItem->doRestoreToStage();
        } else {
            $restoredItem->writeToStage(Versioned::DRAFT);
            $restoredItem = Versioned::get_by_stage($restoredItem->classname, Versioned::DRAFT)
                ->byID($restoredItem->ID);
        }


        $gridField->getList()->remove($item);
    }
}
