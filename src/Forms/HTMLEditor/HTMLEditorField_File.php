<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DateField_Disabled;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\ViewableData;

/**
 * Encapsulation of a file which can either be a remote URL
 * or a {@link File} on the local filesystem, exhibiting common properties
 * such as file name or the URL.
 *
 * @todo Remove once core has support for remote files
 */
abstract class HTMLEditorField_File extends ViewableData
{

    /**
     * Default insertion width for Images and Media
     *
     * @config
     * @var int
     */
    private static $insert_width = 600;

    /**
     * Default insert height for images and media
     *
     * @config
     * @var int
     */
    private static $insert_height = 360;

    /**
     * Max width for insert-media preview.
     *
     * Matches CSS rule for .cms-file-info-preview
     *
     * @var int
     */
    private static $media_preview_width = 176;

    /**
     * Max height for insert-media preview.
     *
     * Matches CSS rule for .cms-file-info-preview
     *
     * @var int
     */
    private static $media_preview_height = 128;

    private static $casting = array(
        'URL' => 'Varchar',
        'Name' => 'Varchar'
    );

    /**
     * Absolute URL to asset
     *
     * @var string
     */
    protected $url;

    /**
     * File dataobject (if available)
     *
     * @var File
     */
    protected $file;

    /**
     * @param string $url
     * @param File $file
     */
    public function __construct($url, File $file = null)
    {
        $this->url = $url;
        $this->file = $file;
        $this->failover = $file;
        parent::__construct();
    }

    /**
     * @return FieldList
     */
    public function getFields()
    {
        $fields = new FieldList(
            CompositeField::create(
                CompositeField::create(LiteralField::create("ImageFull", $this->getPreview()))
                    ->setName("FilePreviewImage")
                    ->addExtraClass('cms-file-info-preview'),
                CompositeField::create($this->getDetailFields())
                    ->setName("FilePreviewData")
                    ->addExtraClass('cms-file-info-data')
            )
                ->setName("FilePreview")
                ->addExtraClass('cms-file-info'),
            TextField::create('CaptionText', _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.CAPTIONTEXT', 'Caption text')),
            DropdownField::create(
                'CSSClass',
                _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.CSSCLASS', 'Alignment / style'),
                array(
                    'leftAlone' => _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
                    'center' => _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
                    'left' => _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
                    'right' => _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.')
                ),
                HtmlEditorField::config()->uninherited('media_alignment')
            ),
            FieldGroup::create(
                _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.IMAGEDIMENSIONS', 'Dimensions'),
                TextField::create(
                    'Width',
                    _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.IMAGEWIDTHPX', 'Width'),
                    $this->getInsertWidth()
                )->setMaxLength(5),
                TextField::create(
                    'Height',
                    " x " . _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.IMAGEHEIGHTPX', 'Height'),
                    $this->getInsertHeight()
                )->setMaxLength(5)
            )->addExtraClass('dimensions last'),
            HiddenField::create('URL', false, $this->getURL()),
            HiddenField::create('FileID', false, $this->getFileID())
        );
        return $fields;
    }

    /**
     * Get list of fields for previewing this records details
     *
     * @return FieldList
     */
    protected function getDetailFields()
    {
        $fields = new FieldList(
            ReadonlyField::create("FileType", _t('AssetTableField.TYPE', 'File type'), $this->getFileType()),
            HTMLReadonlyField::create(
                'ClickableURL',
                _t('AssetTableField.URL', 'URL'),
                $this->getExternalLink()
            )
        );
        // Get file size
        if ($this->getSize()) {
            $fields->insertAfter(
                'FileType',
                ReadonlyField::create("Size", _t('AssetTableField.SIZE', 'File size'), $this->getSize())
            );
        }
        // Get modified details of local record
        if ($this->getFile()) {
            $fields->push(new DateField_Disabled(
                "Created",
                _t('AssetTableField.CREATED', 'First uploaded'),
                $this->getFile()->Created
            ));
            $fields->push(new DateField_Disabled(
                "LastEdited",
                _t('AssetTableField.LASTEDIT', 'Last changed'),
                $this->getFile()->LastEdited
            ));
        }
        return $fields;
    }

    /**
     * Get file DataObject
     *
     * Might not be set (for remote files)
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get file ID
     *
     * @return int
     */
    public function getFileID()
    {
        if ($file = $this->getFile()) {
            return $file->ID;
        }
        return null;
    }

    /**
     * Get absolute URL
     *
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Get basename
     *
     * @return string
     */
    public function getName()
    {
        return $this->file
            ? $this->file->Name
            : preg_replace('/\?.*/', '', basename($this->url));
    }

    /**
     * Get descriptive file type
     *
     * @return string
     */
    public function getFileType()
    {
        return File::get_file_type($this->getName());
    }

    /**
     * Get file size (if known) as string
     *
     * @return string|false String value, or false if doesn't exist
     */
    public function getSize()
    {
        if ($this->file) {
            return $this->file->getSize();
        }
        return false;
    }

    /**
     * HTML content for preview
     *
     * @return string HTML
     */
    public function getPreview()
    {
        $preview = $this->extend('getPreview');
        if ($preview) {
            return $preview;
        }

        // Generate tag from preview
        $thumbnailURL = Convert::raw2att(
            Controller::join_links($this->getPreviewURL(), "?r=" . rand(1, 100000))
        );
        $fileName = Convert::raw2att($this->Name);
        return sprintf(
            "<img id='thumbnailImage' class='thumbnail-preview'  src='%s' alt='%s' />\n",
            $thumbnailURL,
            $fileName
        );
    }

    /**
     * HTML Content for external link
     *
     * @return string
     */
    public function getExternalLink()
    {
        $title = $this->file
            ? $this->file->getTitle()
            : $this->getName();
        return sprintf(
            '<a href="%1$s" title="%2$s" target="_blank" rel="external" class="file-url">%1$s</a>',
            Convert::raw2att($this->url),
            Convert::raw2att($title)
        );
    }

    /**
     * Generate thumbnail url
     *
     * @return string
     */
    public function getPreviewURL()
    {
        // Get preview from file
        if ($this->file) {
            return $this->getFilePreviewURL();
        }

        // Generate default icon html
        return File::get_icon_for_extension($this->getExtension());
    }

    /**
     * Generate thumbnail URL from file dataobject (if available)
     *
     * @return string
     */
    protected function getFilePreviewURL()
    {
        // Get preview from file
        if ($this->file) {
            $width = HTMLEditorField_File::config()->media_preview_width;
            $height = HTMLEditorField_File::config()->media_preview_height;
            return $this->file->ThumbnailURL($width, $height);
        }
        return null;
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension()
    {
        $extension = File::get_file_extension($this->getName());
        return strtolower($extension);
    }

    /**
     * Category name
     *
     * @return string
     */
    public function appCategory()
    {
        if ($this->file) {
            return $this->file->appCategory();
        } else {
            return File::get_app_category($this->getExtension());
        }
    }

    /**
     * Get height of this item
     */
    public function getHeight()
    {
        if ($this->file) {
            $height = $this->file->getHeight();
            if ($height) {
                return $height;
            }
        }
        return HTMLEditorField_File::config()->insert_height;
    }

    /**
     * Get width of this item
     *
     * @return int
     */
    public function getWidth()
    {
        if ($this->file) {
            $width = $this->file->getWidth();
            if ($width) {
                return $width;
            }
        }
        return HTMLEditorField_File::config()->insert_width;
    }

    /**
     * Provide an initial width for inserted media, restricted based on $embed_width
     *
     * @return int
     */
    public function getInsertWidth()
    {
        $width = $this->getWidth();
        $maxWidth = HTMLEditorField_File::config()->insert_width;
        return ($width <= $maxWidth) ? $width : $maxWidth;
    }

    /**
     * Provide an initial height for inserted media, scaled proportionally to the initial width
     *
     * @return int
     */
    public function getInsertHeight()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $maxWidth = HTMLEditorField_File::config()->insert_width;
        return ($width <= $maxWidth) ? $height : round($height * ($maxWidth / $width));
    }
}
