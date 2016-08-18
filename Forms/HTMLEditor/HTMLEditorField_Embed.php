<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Control\SS_HTTPResponse_Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use Embed\Adapters\AdapterInterface;
use Embed\Embed;

/**
 * Encapsulation of an embed tag, linking to an external media source.
 *
 * @see Embed
 */
class HTMLEditorField_Embed extends HTMLEditorField_File
{

	private static $casting = array(
		'Type' => 'Varchar',
		'Info' => 'Varchar'
	);

	/**
	 * Embed result
	 *
	 * @var Embed
	 */
	protected $embed;

	public function __construct($url, File $file = null)
	{
		parent::__construct($url, $file);
		$this->embed = Embed::create($url);
		if (!$this->embed) {
			$controller = Controller::curr();
			$response = $controller->getResponse();
			$response->addHeader('X-Status',
				rawurlencode(_t(
					'HTMLEditorField.URLNOTANOEMBEDRESOURCE',
					"The URL '{url}' could not be turned into a media resource.",
					"The given URL is not a valid Oembed resource; the embed element couldn't be created.",
					array('url' => $url)
				)));
			$response->setStatusCode(404);

			throw new SS_HTTPResponse_Exception($response);
		}
	}

	/**
	 * Get file-edit fields for this filed
	 *
	 * @return FieldList
	 */
	public function getFields()
	{
		$fields = parent::getFields();
		if ($this->Type === 'photo') {
			$fields->insertBefore('CaptionText', new TextField(
				'AltText',
				_t('HTMLEditorField.IMAGEALTTEXT', 'Alternative text (alt) - shown if image can\'t be displayed'),
				$this->Title,
				80
			));
			$fields->insertBefore('CaptionText', new TextField(
				'Title',
				_t('HTMLEditorField.IMAGETITLE', 'Title text (tooltip) - for additional information about the image')
			));
		}
		return $fields;
	}

	/**
	 * Get width of this Embed
	 *
	 * @return int
	 */
	public function getWidth()
	{
		return $this->embed->width ?: 100;
	}

	/**
	 * Get height of this Embed
	 *
	 * @return int
	 */
	public function getHeight()
	{
		return $this->embed->height ?: 100;
	}

	public function getPreviewURL()
	{
		// Use thumbnail url
		if ($this->embed->image) {
			return $this->embed->image;
		}

		// Use direct image type
		if ($this->getType() == 'photo' && !empty($this->embed->url)) {
			return $this->embed->url;
		}

		// Default media
		return FRAMEWORK_DIR . '/images/default_media.png';
	}

	public function getName()
	{
		if ($this->embed->title) {
			return $this->embed->title;
		} else {
			return parent::getName();
		}
	}

	/**
	 * Get Embed type
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->embed->type;
	}

	/**
	 * Get filetype
	 *
	 * @return string
	 */
	public function getFileType()
	{
		return $this->getType()
			?: parent::getFileType();
	}

	/**
	 * @return AdapterInterface
	 */
	public function getEmbed()
	{
		return $this->embed;
	}

	public function appCategory()
	{
		return 'embed';
	}

	/**
	 * Info for this Embed
	 *
	 * @return string
	 */
	public function getInfo()
	{
		return $this->embed->info;
	}
}
