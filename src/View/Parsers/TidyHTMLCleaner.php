<?php

namespace SilverStripe\View\Parsers;

use tidy;

/**
 * Cleans HTML using the Tidy package
 * http://php.net/manual/en/book.tidy.php
 */
class TidyHTMLCleaner extends HTMLCleaner
{

    protected $defaultConfig = [
        'clean' => true,
        'output-xhtml' => true,
        'show-body-only' => true,
        'wrap' => 0,
        'doctype' => 'omit',
        'input-encoding' => 'utf8',
        'output-encoding' => 'utf8'
    ];

    public function cleanHTML($content)
    {
        $tidy = new tidy();
        $output = $tidy->repairString($content, $this->config);

        // Clean leading/trailing whitespace
        return preg_replace('/(^\s+)|(\s+$)/', '', $output ?? '');
    }
}
