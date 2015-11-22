<?php

namespace SilverStripe\Filesystem\Storage;

/**
 * Interface to define a handler for persistent generated files
 *
 * @package framework
 * @subpackage filesystem
 */
interface GeneratedAssetHandler {

	/**
	 * Given a filename and entropy, determine if a pre-generated file is valid. If this file is invalid
	 * or expired, invoke $callback to regenerate the content.
	 *
	 * Returns a URL to the generated file
	 *
	 * @param string $filename
	 * @param mixed $entropy
	 * @param callable $callback To generate content. If none provided, url will only be returned
	 * if there is valid content.
	 * @return string URL to generated file
	 */
	public function getGeneratedURL($filename, $entropy, $callback);

	/**
	 * Given a filename and entropy, determine if a pre-generated file is valid. If this file is invalid
	 * or expired, invoke $callback to regenerate the content.
	 *
	 * @param string $filename
	 * @param mixed $entropy
	 * @param callable $callback To generate content. If none provided, content will only be returned
	 * if there is valid content.
	 * @return string Content for this generated file
	 */
	public function getGeneratedContent($filename, $entropy, $callback);
}
