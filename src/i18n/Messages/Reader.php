<?php

namespace SilverStripe\i18n\Messages;

/**
 * Message reader. Inverse of Writer
 */
interface Reader
{
    /**
     * Get messages from this locale
     *
     * @param string $locale
     * @param string $path Filename (or other identifier)
     * @return array messages Flat array of localisation keys to values.
     */
    public function read($locale, $path);
}
