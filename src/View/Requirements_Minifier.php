<?php

namespace SilverStripe\View;

/**
 * Provides an abstract interface for minifying content
 *
 * @deprecated 4.0..5.0
 */
interface Requirements_Minifier
{

    /**
     * Minify the given content
     *
     * @param string $content
     * @param string $type Either js or css
     * @param string $filename Name of file to display in case of error
     * @return string minified content
     */
    public function minify($content, $type, $filename);
}
