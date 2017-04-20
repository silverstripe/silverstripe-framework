<?php

namespace SilverStripe\View\Parsers;

use SilverStripe\Core\Injector\Injector;
use HTMLPurifier;

/**
 * Cleans HTML using the HTMLPurifier package
 * http://htmlpurifier.org/
 */
class PurifierHTMLCleaner extends HTMLCleaner
{
    public function cleanHTML($content)
    {
        $html = new HTMLPurifier();
        $doc = Injector::inst()->create('HTMLValue', $html->purify($content));
        return $doc->getContent();
    }
}
