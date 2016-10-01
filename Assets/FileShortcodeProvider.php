<?php

namespace SilverStripe\Assets\ViewSupport;

use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\Parsers\ShortcodeHandler;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Class FileShortcodeProvider
 *
 * @package SilverStripe\Forms\HTMLEditor
 */
class FileShortcodeProvider implements ShortcodeHandler
{
	use Extensible;
	use Injectable;

	/**
	 * Gets the list of shortcodes provided by this handler
	 *
	 * @return mixed
	 */
	public static function get_shortcodes()
	{
		return 'file_link';
	}

	/**
	 * Replace "[file_link id=n]" shortcode with an anchor tag or link to the file.
	 *
	 * @param array $arguments Arguments passed to the parser
	 * @param string $content Raw shortcode
	 * @param ShortcodeParser $parser Parser
	 * @param string $shortcode Name of shortcode used to register this handler
	 * @param array $extra Extra arguments
	 *
	 * @return string Result of the handled shortcode
	 */
	public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = array())
	{
		// Find appropriate record, with fallback for error handlers
		$record = static::find_shortcode_record($arguments, $errorCode);
		if ($errorCode) {
			$record = static::find_error_record($errorCode);
		}
		if (!$record) {
			return null; // There were no suitable matches at all.
		}

		// build the HTML tag
		if ($content) {
			// build some useful meta-data (file type and size) as data attributes
			$attrs = ' ';
			if ($record instanceof File) {
				foreach (array(
							 'class'     => 'file',
							 'data-type' => $record->getExtension(),
							 'data-size' => $record->getSize()
						 ) as $name => $value) {
					$attrs .= sprintf('%s="%s" ', $name, $value);
				}
			}

			return sprintf('<a href="%s" %s>%s</a>', $record->Link(), trim($attrs), $parser->parse($content));
		} else {
			return $record->Link();
		}
	}

	/**
	 * Find the record to use for a given shortcode.
	 *
	 * @param array $args Array of input shortcode arguments
	 * @param int $errorCode If the file is not found, or is inaccessible, this will be assigned to a HTTP error code.
	 *
	 * @return File|null The File DataObject, if it can be found.
	 */
	public static function find_shortcode_record($args, &$errorCode = null)
	{
		// Validate shortcode
		if (!isset($args['id']) || !is_numeric($args['id'])) {
			return null;
		}

		// Check if the file is found
		/** @var File $file */
		$file = File::get()->byID($args['id']);
		if (!$file) {
			$errorCode = 404;

			return null;
		}

		// Check if the file is viewable
		if (!$file->canView()) {
			$errorCode = 403;

			return null;
		}

		// Success
		return $file;
	}


	/**
	 * Given a HTTP Error, find an appropriate substitute File or SiteTree data object instance.
	 *
	 * @param int $errorCode HTTP Error value
	 *
	 * @return File|SiteTree File or SiteTree object to use for the given error
	 */
	protected static function find_error_record($errorCode)
	{
		$result = static::singleton()->invokeWithExtensions('getErrorRecordFor', $errorCode);
		$result = array_filter($result);
		if ($result) {
			return reset($result);
		}

		return null;
	}

}
