<?php

namespace SilverStripe\Control\Tests\RSS\RSSFeedTest;

use SilverStripe\Model\ModelData;

class ItemB extends ModelData
{
    // ItemB tests without $casting

    public function Title()
    {
        return "ItemB";
    }

    public function AbsoluteLink()
    {
        return "http://www.example.com/item-b.html";
    }

    public function Content()
    {
        return "ItemB Content";
    }

    public function AltContent()
    {
        return "ItemB AltContent";
    }
}
