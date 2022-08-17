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
class GridFieldEditButton extends AbstractGridFieldComponent implements GridField_ColumnProvider, GridField_ActionProvider, GridField_ActionMenuLink
{
    use GridFieldStateAware;

    /**
     * HTML classes to be added to GridField edit buttons
     *
     * @var string[]
     */
    protected $extraClass = [
        'grid-field__icon-action--hidden-on-hover' => true,
        'font-icon-edit' => true,
        'btn--icon-large' => true,
        'action-menu--handled' => true
    ];

    /**
     * @inheritdoc
     */
    public function getTitle(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Security\Member $record, string $columnName): string
    {
        return _t(__CLASS__ . '.EDIT', "Edit");
    }

    /**
     * @inheritdoc
     */
    public function getGroup(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Security\Member $record, string $columnName): string
    {
        return GridField_ActionMenuItem::DEFAULT_GROUP;
    }

    /**
     * @inheritdoc
     */
    public function getExtraData(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Security\Member $record, string $columnName): array
    {
        return [
            "classNames" => "font-icon-edit action-detail edit-link"
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUrl(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\UserForms\Model\EditableFormField\EditableFormStep $record, string $columnName, bool $addState = true): string
    {
        $link = Controller::join_links(
            $gridField->Link('item'),
            $record->ID,
            'edit'
        );

        if ($addState) {
            $link = $this->getStateManager()->addStateToURL($gridField, $link);
        }

        return $link;
    }

    /**
     * Add a column 'Delete'
     *
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns(SilverStripe\Forms\GridField\GridField $gridField, &$columns): void
    {
        if (!in_array('Actions', $columns ?? [])) {
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
    public function getColumnAttributes(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\UserForms\Model\EditableFormField\EditableFormStep $record, string $columnName): array
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
    public function getColumnMetadata(SilverStripe\Forms\GridField\GridField $gridField, string $columnName): array
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
    public function getColumnsHandled(SilverStripe\Forms\GridField\GridField $gridField): array
    {
        return ['Actions'];
    }

    /**
     * Which GridField actions are this component handling.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions(SilverStripe\Forms\GridField\GridField $gridField): array
    {
        return [];
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string The HTML for the column
     */
    public function getColumnContent(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\UserForms\Model\EditableFormField\EditableFormStep $record, string $columnName): SilverStripe\ORM\FieldType\DBHTMLText
    {
        // No permission checks, handled through GridFieldDetailForm,
        // which can make the form readonly if no edit permissions are available.

        $data = new ArrayData([
            'Link' => $this->getURL($gridField, $record, $columnName, false),
            'ExtraClass' => $this->getExtraClass()
        ]);

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return $data->renderWith($template);
    }

    /**
     * Get the extra HTML classes to add for edit buttons
     *
     * @return string
     */
    public function getExtraClass(): string
    {
        return implode(' ', array_keys($this->extraClass ?? []));
    }

    /**
     * Add an extra HTML class
     *
     * @param string $class
     * @return $this
     */
    public function addExtraClass(string $class): SilverStripe\Forms\GridField\GridFieldEditButton
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
    public function removeExtraClass(string $class): SilverStripe\Forms\GridField\GridFieldEditButton
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
