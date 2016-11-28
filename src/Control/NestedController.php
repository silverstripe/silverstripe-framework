<?php

namespace SilverStripe\Control;

/**
 * Interface that is implemented by controllers that are designed to hand control over to another controller.
 * ModelAsController, which selects up a SiteTree object and passes control over to a suitable subclass of ContentController, is a good
 * example of this.
 *
 * Controllers that implement this interface must always return a nested controller.
 */
interface NestedController
{

    /**
     * Get overriding controller
     *
     * @return Controller
     */
    public function getNestedController();
}
