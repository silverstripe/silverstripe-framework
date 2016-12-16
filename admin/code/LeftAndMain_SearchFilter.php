<?php

namespace SilverStripe\Admin;

use SilverStripe\ORM\DataObject;

/**
 * Abstract interface for a class which may be used to filter the results displayed
 * in a nested tree
 */
interface LeftAndMain_SearchFilter
{

    /**
     * Method on {@link Hierarchy} objects which is used to traverse into children relationships.
     *
     * @return string
     */
    public function getChildrenMethod();

    /**
     * Method on {@link Hierarchy} objects which is used find the number of children for a parent page
     *
     * @return string
     */
    public function getNumChildrenMethod();


    /**
     * Returns TRUE if the given page should be included in the tree.
     * Caution: Does NOT check view permissions on the page.
     *
     * @param DataObject $page
     * @return bool
     */
    public function isPageIncluded($page);

    /**
     * Given a page, determine any additional CSS classes to apply to the tree node
     *
     * @param DataObject $page
     * @return array|string
     */
    public function getPageClasses($page);
}
