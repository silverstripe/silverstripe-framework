<?php

namespace SilverStripe\Admin;

use SilverStripe\Core\Extension;

/**
 * Plug-ins for additional functionality in your LeftAndMain classes.
 */
abstract class LeftAndMainExtension extends Extension
{

    public function init()
    {
    }

    public function accessedCMS()
    {
    }

    public function augmentNewSiteTreeItem(&$item)
    {
    }
}
