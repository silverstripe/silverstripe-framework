<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;

/**
 * GridFieldLazyLoader alters the {@link GridField} behavior to delay rendering of rows until the tab containing the
 * GridField is selected by the user.
 *
 * @see GridField
 */
class GridFieldLazyLoader implements GridField_DataManipulator, GridField_HTMLProvider
{

    /**
     * @inheritDoc
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        return $this->isLazy($gridField) ?
            ArrayList::create([]) :
            $dataList;
    }

    /**
     * @inheritDoc
     */
    public function getHTMLFragments($gridField)
    {
        $gridField->addExtraClass($this->isLazy($gridField) ?
            'grid-field-lazy-loadable' :
            'grid-field-lazy-loaded');
        return [];
    }

    /**
     * Detect if the current request should include results
     * @param GridField $gridField
     * @return bool
     */
    private function isLazy(GridField $gridField)
    {
        return $gridField->getRequest()->getHeader('X-Pjax') !== 'CurrentField';
    }
}
