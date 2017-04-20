<?php

namespace SilverStripe\Control\Tests\RSS\RSSFeedTest;

use SilverStripe\View\ViewableData;

class ItemD extends ViewableData
{
    // ItemD test fields - all fields use casting but Content & AltContent cast as HTMLText
    private static $casting = array(
        'Title' => 'Varchar',
        'Content' => 'HTMLText', // Supports shortcodes
    );

    public $Title = 'ItemD';
    public $Content = '<p>ItemD Content [test_shortcode]</p>';

    public function Link()
    {
        return 'item-d.html';
    }

    public function AbsoluteLink()
    {
        return 'http://www.example.org/item-d.html';
    }
}
