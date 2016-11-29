<?php

namespace SilverStripe\Assets\Storage;

/**
 * Interface to define a handler for persistent generated files
 */
interface GeneratedAssetHandler
{

    /**
     * Returns a URL to a generated asset, if one is available.
     *
     * Given a filename, determine if a file is available. If the file is unavailable,
     * and a callback is supplied, invoke it to regenerate the content.
     *
     * @param string $filename
     * @param callable $callback To generate content. If none provided, url will only be returned
     * if there is valid content.
     * @return string URL to generated file
     */
    public function getContentURL($filename, $callback = null);

    /**
     * Returns the content for a generated asset, if one is available.
     *
     * Given a filename, determine if a file is available. If the file is unavailable,
     * and a callback is supplied, invoke it to regenerate the content.
     *
     * @param string $filename
     * @param callable $callback To generate content. If none provided, content will only be returned
     * if there is valid content.
     * @return string Content for this generated file
     */
    public function getContent($filename, $callback = null);

    /**
     * Update content with new value
     *
     * @param string $filename
     * @param string $content Content to write to the backend
     */
    public function setContent($filename, $content);

    /**
     * Remove any content under the given file.
     *
     * If $filename is a folder, it should delete all files underneath it also.
     *
     * @param string $filename
     */
    public function removeContent($filename);
}
