<?php

namespace SilverStripe\View\Parsers;

use HTMLPurifier;

/**
 * Cleans HTML using the HTMLPurifier package
 * http://htmlpurifier.org/
 */
class PurifierHTMLCleaner extends HTMLCleaner
{
    public function cleanHTML($content)
    {
        if (class_exists(HTMLPurifier::class)) {
            $html = new HTMLPurifier();
            $doc = HTMLValue::create($html->purify($content));
            return $doc->getContent();
        }

        // No class, no-op
        return $content;
    }
}
