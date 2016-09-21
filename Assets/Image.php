<?php

namespace SilverStripe\Assets;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\Parsers\ShortcodeHandler;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;

/**
 * Represents an Image
 */
class Image extends File implements ShortcodeHandler {

	/**
	 * @config
	 * @var string
	 */
	private static $table_name = 'Image';

	/**
	 * @config
	 * @var string
	 */
	private static $singular_name = "Image";

	/**
	 * @config
	 * @var string
	 */
	private static $plural_name = "Images";

	public function __construct($record = null, $isSingleton = false, $model = null, $queryParams = array()) {
		parent::__construct($record, $isSingleton, $model, $queryParams);
		$this->File->setAllowedCategories('image/supported');
	}

	public function getCMSFields() {
		$path = '/' . dirname($this->getFilename());

		$previewLink = Convert::raw2att($this->PreviewLink());
		$image = "<img src=\"{$previewLink}\" class=\"editor__thumbnail\" />";

		$link = $this->Link();

		$content = Tab::create('Main',
			HeaderField::create('TitleHeader', $this->Title, 1)
				->addExtraClass('editor__heading'),
			LiteralField::create("ImageFull", $image)
				->addExtraClass('editor__file-preview'),
			TabSet::create('Editor',
				Tab::create('Details',
					TextField::create("Title", $this->fieldLabel('Title')),
					TextField::create("Name", $this->fieldLabel('Filename')),
					ReadonlyField::create(
						"Path",
						_t('AssetTableField.PATH', 'Path'),
						(($path !== '/.') ? $path : '') . '/'
					),
					HTMLReadonlyField::create(
						'ClickableURL',
						_t('AssetTableField.URL','URL'),
						sprintf('<i class="%s"></i><a href="%s" target="_blank">%s</a>',
							'font-icon-link btn--icon-large form-control-static__icon', $link, $link)
					)
				),
				Tab::create('Usage',
					DatetimeField::create(
						"Created",
						_t('AssetTableField.CREATED', 'First uploaded')
					)->setReadonly(true),
					DatetimeField::create(
						"LastEdited",
						_t('AssetTableField.LASTEDIT', 'Last changed')
					)->setReadonly(true)
				)
			),
			HiddenField::create('ID', $this->ID)
		);

		if ($dimensions = $this->getDimensions()) {
			$content->insertAfter(
				'TitleHeader',
				LiteralField::create(
					"DisplaySize",
					sprintf('<div class="editor__specs">%spx, %s</div>',
						$dimensions, $this->getSize())
				)
			);
		}

		$fields = FieldList::create(TabSet::create('Root', $content));

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	public function getIsImage() {
		return true;
	}

	/**
	 * Replace"[image id=n]" shortcode with an image reference.
	 * Permission checks will be enforced by the file routing itself.
	 *
	 * @param array $args Arguments passed to the parser
	 * @param string $content Raw shortcode
	 * @param ShortcodeParser $parser Parser
	 * @param string $shortcode Name of shortcode used to register this handler
	 * @param array $extra Extra arguments
	 * @return string Result of the handled shortcode
	 */
	public static function handle_shortcode($args, $content, $parser, $shortcode, $extra = array()) {
		// Find appropriate record, with fallback for error handlers
		$record = static::find_shortcode_record($args, $errorCode);
		if($errorCode) {
			$record = static::find_error_record($errorCode);
		}
		if (!$record) {
			return null; // There were no suitable matches at all.
		}

		// Check if a resize is required
		$src = $record->Link();
		if($record instanceof Image) {
			$width = isset($args['width']) ? $args['width'] : null;
			$height = isset($args['height']) ? $args['height'] : null;
			$hasCustomDimensions = ($width && $height);
			if ($hasCustomDimensions && (($width != $record->getWidth()) || ($height != $record->getHeight()))) {
				$resized = $record->ResizedImage($width, $height);
				// Make sure that the resized image actually returns an image
				if($resized) {
					$src = $resized->getURL();
				}
			}
		}

		// Build the HTML tag
		$attrs = array_merge(
			// Set overrideable defaults
			['src' => '', 'alt' => $record->Title],
			// Use all other shortcode arguments
			$args,
			// But enforce some values
			['id' => '', 'src' => $src]
		);

		// Clean out any empty attributes
		$attrs = array_filter($attrs, function($v) {return (bool)$v;});

		// Condense to HTML attribute string
		$attrsStr = join(' ', array_map(function($name) use ($attrs) {
			return Convert::raw2att($name) . '="' . Convert::raw2att($attrs[$name]) . '"';
		}, array_keys($attrs)));

		return '<img ' . $attrsStr . ' />';
	}

	/**
	 * Regenerates "[image id=n]" shortcode with new src attribute prior to being edited within the CMS.
	 *
	 * @param array $args Arguments passed to the parser
	 * @param string $content Raw shortcode
	 * @param ShortcodeParser $parser Parser
	 * @param string $shortcode Name of shortcode used to register this handler
	 * @param array $extra Extra arguments
	 * @return string Result of the handled shortcode
	 */
	public static function regenerate_shortcode($args, $content, $parser, $shortcode, $extra = array()) {
		// Check if there is a suitable record
		$record = static::find_shortcode_record($args);
		if($record) {
			$args['src'] = $record->getURL();
		}

		// Rebuild shortcode
		$parts = array();
		foreach($args as $name => $value) {
			$htmlValue = Convert::raw2att($value ?: $name);
			$parts[] = sprintf('%s="%s"', $name, $htmlValue);
		}
		return sprintf("[%s %s]", $shortcode, implode(' ', $parts));
	}

	/**
	 * Helper method to regenerate all shortcode links.
	 *
	 * @param string $value HTML value
	 * @return string value with links resampled
	 */
	public static function regenerate_html_links($value) {
		// Create a shortcode generator which only regenerates links
		$regenerator = ShortcodeParser::get('regenerator');
		return $regenerator->parse($value);
	}

	public function PreviewLink($action = null) {
		// Since AbsoluteLink can whitelist protected assets,
		// do permission check first
		if(!$this->canView()) {
			return false;
		}

		// Size to width / height
		$width = (int)$this->config()->get('asset_preview_width');
		$height = (int)$this->config()->get('asset_preview_height');
		$resized = $this->FitMax($width, $height);
		if ($resized && $resized->exists()) {
			$link = $resized->getAbsoluteURL();
		} else {
			$link = $this->getIcon();
		}
		$this->extend('updatePreviewLink', $link, $action);
		return $link;
	}
}
