<?php

namespace SilverStripe\Forms\GridField;

/**
 * A simple readonly, paginated view of records, with sortable and searchable
 * headers.
 */
class GridFieldConfig_Base extends GridFieldConfig
{

    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct();
        $this->addComponent(GridFieldToolbarHeader::create());
        $this->addComponent(GridFieldButtonRow::create('before'));
        $this->addComponent(GridFieldSortableHeader::create());
        $this->addComponent(GridFieldFilterHeader::create());
        $this->addComponent(GridFieldDataColumns::create());
        $this->addComponent(GridFieldPageCount::create('toolbar-header-right'));
        $this->addComponent(GridFieldPaginator::create($itemsPerPage));
        $this->extend('updateConfig');
    }
}
