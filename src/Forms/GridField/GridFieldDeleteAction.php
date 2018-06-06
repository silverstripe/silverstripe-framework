<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;

/**
 * This class is a {@link GridField} component that adds a delete action for
 * objects.
 *
 * This component also supports unlinking a relation instead of deleting the
 * object.
 *
 * Use the {@link $removeRelation} property set in the constructor.
 *
 * <code>
 * $action = new GridFieldDeleteAction(); // delete objects permanently
 *
 * // removes the relation to object instead of deleting
 * $action = new GridFieldDeleteAction(true);
 * </code>
 */
class GridFieldDeleteAction implements GridField_ColumnProvider, GridField_ActionProvider, GridField_ActionMenuItem
{

    /**
     * If this is set to true, this {@link GridField_ActionProvider} will
     * remove the object from the list, instead of deleting.
     *
     * In the case of a has one, has many or many many list it will uncouple
     * the item from the list.
     *
     * @var boolean
     */
    protected $removeRelation = false;

    /**
     *
     * @param boolean $removeRelation - true if removing the item from the list, but not deleting it
     */
    public function __construct($removeRelation = false)
    {
        $this->setRemoveRelation($removeRelation);
    }

    /**
     * @inheritdoc
     */
    public function getTitle($gridField, $record, $columnName)
    {
        $field = $this->getRemoveAction($gridField, $record, $columnName);

        if ($field) {
            return $field->getAttribute('title');
        }

        return _t(__CLASS__ . '.Delete', "Delete");
    }

    /**
     * @inheritdoc
     */
    public function getGroup($gridField, $record, $columnName)
    {
        $field = $this->getRemoveAction($gridField, $record, $columnName);

        return $field ? GridField_ActionMenuItem::DEFAULT_GROUP: null;
    }

    /**
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string|null the attribles for the action
     */
    public function getExtraData($gridField, $record, $columnName)
    {

        $field = $this->getRemoveAction($gridField, $record, $columnName);

        if ($field) {
            return $field->getAttributes();
        }

        return null;
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
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return ['deleterecord', 'unlinkrelation'];
    }

    /**
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string|null the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $field = $this->getRemoveAction($gridField, $record, $columnName);

        if ($field) {
            return $field->Field();
        }

        return null;
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data Form data
     * @throws ValidationException
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'deleterecord' || $actionName == 'unlinkrelation') {
            /** @var DataObject $item */
            $item = $gridField->getList()->byID($arguments['RecordID']);
            if (!$item) {
                return;
            }

            if ($actionName == 'deleterecord') {
                if (!$item->canDelete()) {
                    throw new ValidationException(
                        _t(__CLASS__ . '.DeletePermissionsFailure', "No delete permissions")
                    );
                }

                $item->delete();
            } else {
                if (!$item->canEdit()) {
                    throw new ValidationException(
                        _t(__CLASS__ . '.EditPermissionsFailure', "No permission to unlink record")
                    );
                }

                $gridField->getList()->remove($item);
            }
        }
    }

    /**
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return GridField_FormAction|null
     */
    private function getRemoveAction($gridField, $record, $columnName)
    {
        if ($this->getRemoveRelation()) {
            if (!$record->canEdit()) {
                return null;
            }
            $title = _t(__CLASS__ . '.UnlinkRelation', "Unlink");

            $field = GridField_FormAction::create(
                $gridField,
                'UnlinkRelation' . $record->ID,
                false,
                "unlinkrelation",
                ['RecordID' => $record->ID]
            )
                ->addExtraClass('btn btn--no-text btn--icon-md font-icon-link-broken grid-field__icon-action gridfield-button-unlink action-menu--handled')
                ->setAttribute('classNames', 'gridfield-button-unlink font-icon-link-broken')
                ->setDescription($title)
                ->setAttribute('aria-label', $title);
        } else {
            if (!$record->canDelete()) {
                return null;
            }
            $title = _t(__CLASS__ . '.Delete', "Delete");

            $field = GridField_FormAction::create(
                $gridField,
                'DeleteRecord' . $record->ID,
                false,
                "deleterecord",
                ['RecordID' => $record->ID]
            )
                ->addExtraClass('gridfield-button-delete btn--icon-md font-icon-trash-bin btn--no-text grid-field__icon-action action-menu--handled')
                ->setAttribute('classNames', 'gridfield-button-delete font-icon-trash')
                ->setDescription($title)
                ->setAttribute('aria-label', $title);
        }

        return $field;
    }

    /**
     * Get whether to remove or delete the relation
     *
     * @return bool
     */
    public function getRemoveRelation()
    {
        return $this->removeRelation;
    }

    /**
     * Set whether to remove or delete the relation
     * @param bool $removeRelation
     * @return $this
     */
    public function setRemoveRelation($removeRelation)
    {
        $this->removeRelation = (bool) $removeRelation;
        return $this;
    }
}
