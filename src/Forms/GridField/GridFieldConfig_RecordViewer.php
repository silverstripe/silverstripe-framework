<?php declare(strict_types = 1);

namespace SilverStripe\Forms\GridField;

/**
 * Allows viewing readonly details of individual records.
 */
class GridFieldConfig_RecordViewer extends GridFieldConfig_Base
{

    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);

        $this->addComponent(new GridFieldViewButton());
        $this->addComponent(new GridFieldDetailForm());
        $this->removeComponentsByType(GridFieldFilterHeader::class);

        $this->extend('updateConfig');
    }
}
