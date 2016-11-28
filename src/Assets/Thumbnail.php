<?php

namespace SilverStripe\Assets;

/**
 * An object which may have a thumbnail url
 */
interface Thumbnail
{

    /**
     * Get a thumbnail for this object
     *
     * @param int $width Preferred width of the thumbnail
     * @param int $height Preferred height of the thumbnail
     * @return string URL to the thumbnail, if available
     */
    public function ThumbnailURL($width, $height);
}
