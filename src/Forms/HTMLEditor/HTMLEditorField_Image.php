<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Assets\File;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;

/**
 * Encapsulation of an image tag, linking to an image either internal or external to the site.
 */
class HTMLEditorField_Image extends HTMLEditorField_File
{

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * File size details
     *
     * @var string
     */
    protected $size;

    public function __construct($url, File $file = null)
    {
        parent::__construct($url, $file);

        if ($file) {
            return;
        }

        // Get size of remote file
        $size = @filesize($url);
        if ($size) {
            $this->size = $size;
        }

        // Get dimensions of remote file
        $info = @getimagesize($url);
        if ($info) {
            $this->width = $info[0];
            $this->height = $info[1];
        }
    }

    public function getFields()
    {
        $fields = parent::getFields();

        // Alt text
        $fields->insertBefore(
            'CaptionText',
            TextField::create(
                'AltText',
                _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.IMAGEALT', 'Alternative text (alt)'),
                $this->Title,
                80
            )->setDescription(
                _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.IMAGEALTTEXTDESC', 'Shown to screen readers or if image can\'t be displayed')
            )
        );

        // Tooltip
        $fields->insertAfter(
            'AltText',
            TextField::create(
                'Title',
                _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.IMAGETITLETEXT', 'Title text (tooltip)')
            )->setDescription(
                _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.IMAGETITLETEXTDESC', 'For additional information about the image')
            )
        );

        return $fields;
    }

    protected function getDetailFields()
    {
        $fields = parent::getDetailFields();
        $width = $this->getOriginalWidth();
        $height = $this->getOriginalHeight();

        // Show dimensions of original
        if ($width && $height) {
            $fields->insertAfter(
                'ClickableURL',
                ReadonlyField::create(
                    "OriginalWidth",
                    _t('SilverStripe\\AssetAdmin\\Controller\\AssetAdmin.WIDTH', 'Width'),
                    $width
                )
            );
            $fields->insertAfter(
                'OriginalWidth',
                ReadonlyField::create(
                    "OriginalHeight",
                    _t('SilverStripe\\AssetAdmin\\Controller\\AssetAdmin.HEIGHT', 'Height'),
                    $height
                )
            );
        }
        return $fields;
    }

    /**
     * Get width of original, if known
     *
     * @return int
     */
    public function getOriginalWidth()
    {
        if ($this->width) {
            return $this->width;
        }
        if ($this->file) {
            $width = $this->file->getWidth();
            if ($width) {
                return $width;
            }
        }
        return null;
    }

    /**
     * Get height of original, if known
     *
     * @return int
     */
    public function getOriginalHeight()
    {
        if ($this->height) {
            return $this->height;
        }

        if ($this->file) {
            $height = $this->file->getHeight();
            if ($height) {
                return $height;
            }
        }
        return null;
    }

    public function getWidth()
    {
        if ($this->width) {
            return $this->width;
        }
        return parent::getWidth();
    }

    public function getHeight()
    {
        if ($this->height) {
            return $this->height;
        }
        return parent::getHeight();
    }

    public function getSize()
    {
        if ($this->size) {
            return File::format_size($this->size);
        }
        return parent::getSize();
    }

    /**
     * Provide an initial width for inserted image, restricted based on $embed_width
     *
     * @return int
     */
    public function getInsertWidth()
    {
        $width = $this->getWidth();
        $maxWidth = HTMLEditorField_Image::config()->insert_width;
        return $width <= $maxWidth
            ? $width
            : $maxWidth;
    }

    /**
     * Provide an initial height for inserted image, scaled proportionally to the initial width
     *
     * @return int
     */
    public function getInsertHeight()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $maxWidth = HTMLEditorField_Image::config()->insert_width;
        return ($width <= $maxWidth) ? $height : round($height * ($maxWidth / $width));
    }

    public function getPreviewURL()
    {
        // Get preview from file
        if ($this->file) {
            return $this->getFilePreviewURL();
        }

        // Embed image directly
        return $this->url;
    }
}
