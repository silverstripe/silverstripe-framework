<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Dev\Deprecation;

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
        $this->addComponent($sort = GridFieldSortableHeader::create());
        $this->addComponent($filter = GridFieldFilterHeader::create());
        $this->addComponent(GridFieldDataColumns::create());
        $this->addComponent(GridFieldPageCount::create('toolbar-header-right'));
        $this->addComponent($pagination = GridFieldPaginator::create($itemsPerPage));

        Deprecation::withNoReplacement(function () use ($sort, $filter, $pagination) {
            $sort->setThrowExceptionOnBadDataType(false);
            $filter->setThrowExceptionOnBadDataType(false);
            $pagination->setThrowExceptionOnBadDataType(false);
        });

        $this->extend('updateConfig');
    }
}
