<?php

namespace SilverStripe\ORM\Tests\DBHTMLTextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\Parsers\ShortcodeHandler;

class TestShortcode implements ShortcodeHandler, TestOnly
{
    public static function get_shortcodes()
    {
        return 'test';
    }

    public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = array())
    {
        return 'shortcode content';
    }
}
