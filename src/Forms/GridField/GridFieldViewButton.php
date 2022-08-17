<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * A button that allows a user to view readonly details of a record. This is
 * disabled by default and intended for use in readonly {@link GridField}
 * instances.
 */
class GridFieldViewButton extends AbstractGridFieldComponent implements GridField_ColumnProvider, GridField_ActionMenuLink
{
    /**
     * @inheritdoc
     */
    public function getTitle(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Category $record, string $columnName): string
    {
        return _t(__CLASS__ . '.VIEW', "View");
    }

    /**
     * @inheritdoc
     */
    public function getGroup(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Category $record, string $columnName): string
    {
        return GridField_ActionMenuItem::DEFAULT_GROUP;
    }

    /**
     * @inheritdoc
     */
    public function getExtraData(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Category $record, string $columnName): array
    {
        return [
            "classNames" => "font-icon-eye action-detail view-link"
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUrl(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Person $record, string $columnName): string
    {
        return Controller::join_links($gridField->Link('item'), $record->ID, 'view');
    }

    public function augmentColumns(SilverStripe\Comments\Admin\CommentsGridField $field, &$columns): void
    {
        if (!in_array('Actions', $columns ?? [])) {
            $columns[] = 'Actions';
        }
    }

    public function getColumnsHandled(SilverStripe\Comments\Admin\CommentsGridField $field): array
    {
        return ['Actions'];
    }

    public function getColumnContent(SilverStripe\Forms\GridField\GridField $field, SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Person $record, string $col): SilverStripe\ORM\FieldType\DBHTMLText
    {
        if (!$record->canView()) {
            return null;
        }
        $data = new ArrayData([
            'Link' => $this->getURL($field, $record, $col),
        ]);
        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return $data->renderWith($template);
    }

    public function getColumnAttributes(SilverStripe\Forms\GridField\GridField $field, SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Person $record, string $col): array
    {
        return ['class' => 'grid-field__col-compact'];
    }

    public function getColumnMetadata(SilverStripe\Comments\Admin\CommentsGridField $gridField, string $col): array
    {
        return ['title' => null];
    }
}
