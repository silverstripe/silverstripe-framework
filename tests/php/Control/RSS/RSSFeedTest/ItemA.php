<?php

namespace SilverStripe\Control\Tests\RSS\RSSFeedTest;

use SilverStripe\Control\Controller;
use SilverStripe\View\ViewableData;

class ItemA extends ViewableData
{
    // RSS-feed items must have $casting/$db information.
    private static $casting = array(
        'Title' => 'Varchar',
        'Content' => 'Text',
        'AltContent' => 'Text',
    );

    public function getTitle()
    {
        return "ItemA";
    }

    public function getContent()
    {
        return "ItemA Content";
    }

    public function getAltContent()
    {
        return "ItemA AltContent";
    }

    public function Link($action = null)
    {
        return Controller::join_links("item-a/", $action);
    }
}
