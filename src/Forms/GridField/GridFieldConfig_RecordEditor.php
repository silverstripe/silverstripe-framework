<?php
namespace SilverStripe\Forms\GridField;

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

        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent($sort = new GridFieldSortableHeader());
        $this->addComponent($filter = new GridFieldFilterHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new GridFieldEditButton());
        $this->addComponent(new GridFieldDeleteAction());
        $this->addComponent(new GridField_ActionMenu());
        $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
        $this->addComponent(new GridFieldDetailForm(null, $showPagination, $showAdd));

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);

        $this->extend('updateConfig');
    }
}
