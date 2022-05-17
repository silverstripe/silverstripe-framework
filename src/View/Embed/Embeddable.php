<?php

namespace SilverStripe\View\Embed;

/**
 * Abstract interface for an embeddable resource
 *
 * @see EmbedContainer
 */
interface Embeddable
{
    /**
     * Get width of this Embed
     *
     * @return int
     */
    public function getWidth();

    /**
     * Get height of this Embed
     *
     * @return int
     */
    public function getHeight();

    /**
     * Get preview url
     *
     * @return string
     */
    public function getPreviewURL();

    /**
     * Get human readable name for this resource
     *
     * @return string
     */
    public function getName();

    /**
     * Get Embed type
     *
     * @return string
     */
    public function getType();

    /**
     * Validate this resource
     *
     * @return bool
     */
    public function validate();
}
