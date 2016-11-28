<?php

namespace SilverStripe\View;

/**
 * Contains references to any number of themes or theme directories
 */
interface ThemeList
{
    /**
     * Returns a map of all themes information. The map is in the following format:
     *
     * <code>
     *   [
     *     '/mysite',
     *     'vendor/module:themename',
     *     '/framework/admin'
     *     'simple'
     *   ]
     * </code>
     *
     * These may be in any format, including vendor/namespace:path, or /absolute-path,
     * but will not include references to any other {@see ThemeContainer} as
     * SSViewer::get_themes() does.
     *
     * @return array
     */
    public function getThemes();
}
