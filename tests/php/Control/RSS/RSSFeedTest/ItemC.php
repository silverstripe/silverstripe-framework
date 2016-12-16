<?php

namespace SilverStripe\Control\Tests\RSS\RSSFeedTest;

use SilverStripe\View\ViewableData;

class ItemC extends ViewableData
{
    // ItemC tests fields - Title has casting, Content doesn't.
    private static $casting = array(
        'Title' => 'Varchar',
        'AltContent' => 'Text',
    );

    public $Title = "ItemC";
    public $Content = "ItemC Content";
    public $AltContent = "ItemC AltContent";

    public function Link()
    {
        return "item-c.html";
    }

    public function AbsoluteLink()
    {
        return "http://www.example.com/item-c.html";
    }
}
