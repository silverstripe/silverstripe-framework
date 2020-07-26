<?php

namespace SilverStripe\View\Parsers;

/**
 * A FilterInterface is given an input string and returns a filtered string. Replacements will be provided and
 * performed (typically in regex format), and transliteration may be used as a separate service to replace
 * characters rather than remove them.
 *
 * For example implementations, see {@link URLSegmentFilter}.
 */
interface FilterInterface
{
    /**
     * Performs a set of replacement rules against the input string, applying transliteration if a service is
     * provided, and returns the filtered result.
     *
     * @param string $input
     * @return string
     */
    public function filter($input);
}
