<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\SS_List;

/**
 * GridFieldLazyLoader alters the {@link GridField} behavior to delay rendering of rows until the tab containing the
 * GridField is selected by the user.
 *
 * @see GridField
 */
class GridFieldLazyLoader extends AbstractGridFieldComponent implements GridField_DataManipulator, GridField_HTMLProvider
{

    /**
     * Empty $datalist if the current request should be lazy loadable.
     *
     * @param GridField $gridField
     * @param SS_List $dataList
     * @return SS_List
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList)
    {
        // If we are lazy loading an empty the list
        if ($this->isLazy($gridField)) {
            if ($dataList instanceof Filterable) {
                // If our original list can be filtered, filter out all results.
                $dataList = $dataList->byIDs([-1]);
            } else {
                // If not, create an empty list instead.
                $dataList = ArrayList::create([]);
            }
        }
        return $dataList;
    }

    /**
     * Apply an appropriate CSS class to `$gridField` based on whatever the current request is lazy loadable or not.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $gridField->addExtraClass($this->isLazy($gridField) ?
            'grid-field--lazy-loadable' :
            'grid-field--lazy-loaded');
        return [];
    }

    /**
     * Detect if the current request should include results.
     * @param GridField $gridField
     * @return bool
     */
    private function isLazy(GridField $gridField)
    {
        return
            $gridField->getRequest()->getHeader('X-Pjax') !== 'CurrentField' &&
            $this->isInTabSet($gridField);
    }

    /**
     * Recursively check if $field is inside a TabSet.
     * @param FormField $field
     * @return bool
     */
    private function isInTabSet(FormField $field)
    {
        $list = $field->getContainerFieldList();
        if ($list && $containerField = $list->getContainerField()) {
            // Classes that extends TabSet might not have the expected JS to lazy load.
            return get_class($containerField) === TabSet::class
                ?: $this->isInTabSet($containerField);
        }

        return false;
    }
}
