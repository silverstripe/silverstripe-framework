<?php
/**
 * Persists versioned state between requests via querystring arguments
 *
 * @property Controller|DataObject $owner
 */
class VersionedStateExtension extends Extension
{
    /**
     * Auto-append current stage if we're in draft,
     * to avoid relying on session state for this,
     * and the related potential of showing draft content
     * without varying the URL itself.
     *
     * Assumes that if the user has access to view the current
     * record in draft stage, they can also view other draft records.
     * Does not concern itself with verifying permissions for performance reasons.
     *
     * This should also pull through to form actions.
     *
     * @param string $link
     */
    public function updateLink(&$link)
    {
        // Skip if link already contains reading mode
        if ($this->hasVersionedQuery($link)) {
            return;
        }

        // Skip if current mode matches default mode
        // See LeftAndMain::init() for example of this being overridden.
        $readingMode = $this->getReadingmode();
        if ($readingMode === Versioned::get_default_reading_mode()) {
            return;
        }

        // Determine if query args are supported for the current mode
        $queryargs = VersionedReadingMode::toQueryString($readingMode);
        if (!$queryargs) {
            return;
        }

        // Decorate
        $link = Controller::join_links(
            $link,
            '?' . http_build_query($queryargs)
        );
    }

    /**
     * Check if link contains versioned queryargs
     *
     * @param string $link
     * @return bool
     */
    protected function hasVersionedQuery($link)
    {
        // Find querystrings
        $parts = explode('?', $link, 2);
        if (count($parts) < 2) {
            return false;
        }

        // Parse args
        $readingMode = VersionedReadingMode::fromQueryString($parts[1]);
        return !empty($readingMode);
    }

    /**
     * Get reading mode for the record / controller being decorated
     *
     * @return string
     */
    protected function getReadingmode()
    {
        $default = Versioned::get_reading_mode();

        // Non dataobjects use global mode
        if (! $this->owner instanceof DataObject) {
            return $default;
        }

        // Respect source query params (so records selected from live will have live urls)
        $queryParams = $this->owner->getSourceQueryParams();
        return VersionedReadingMode::fromDataQueryParams($queryParams)
            // Fall back to default otherwise
            ?: $default;
    }
}
