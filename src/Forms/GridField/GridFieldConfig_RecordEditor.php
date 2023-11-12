<?php
namespace SilverStripe\Forms\GridField;

use SilverStripe\Dev\Deprecation;

/**
 * Allows editing of records contained within the GridField, instead of only allowing the ability to view records in
 * the GridField.
 */
class GridFieldConfig_RecordEditor extends GridFieldConfig
{
    /**
     *
     * @param int $itemsPerPage - How many items per page should show up
     * @param bool $showPagination Whether the `Previous` and `Next` buttons should display or not, leave as null to use default
     * @param bool $showAdd Whether the `Add` button should display or not, leave as null to use default
     */
    public function __construct($itemsPerPage = null, $showPagination = null, $showAdd = null)
    {
        parent::__construct();

        $this->addComponent(GridFieldButtonRow::create('before'));
        $this->addComponent(GridFieldAddNewButton::create('buttons-before-left'));
        $this->addComponent(GridFieldToolbarHeader::create());
        $this->addComponent($sort = GridFieldSortableHeader::create());
        $this->addComponent($filter = GridFieldFilterHeader::create());
        $this->addComponent(GridFieldDataColumns::create());
        $this->addComponent(GridFieldEditButton::create());
        $this->addComponent(GridFieldDeleteAction::create());
        $this->addComponent(GridField_ActionMenu::create());
        $this->addComponent(GridFieldPageCount::create('toolbar-header-right'));
        $this->addComponent($pagination = GridFieldPaginator::create($itemsPerPage));
        $this->addComponent(GridFieldDetailForm::create(null, $showPagination, $showAdd));

        Deprecation::withNoReplacement(function () use ($sort, $filter, $pagination) {
            $sort->setThrowExceptionOnBadDataType(false);
            $filter->setThrowExceptionOnBadDataType(false);
            $pagination->setThrowExceptionOnBadDataType(false);
        });

        $this->extend('updateConfig');
    }
}
