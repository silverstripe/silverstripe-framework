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
class Image extends File {

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
		
        $statusTitle = $this->getStatusTitle();
        $statusFlag = "<span class=\"editor__status-flag\">{$statusTitle}</span>";

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
					sprintf('<div class="editor__specs">%spx, %s %s</div>',
						$dimensions, $this->getSize(), $statusFlag)
				)
			);
		} else {
			$content->insertAfter(
				'TitleHeader',
				LiteralField::create('StatusFlag', $statusFlag)
			);
		}

		$fields = FieldList::create(TabSet::create('Root', $content));

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	public function getIsImage() {
		return true;
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
