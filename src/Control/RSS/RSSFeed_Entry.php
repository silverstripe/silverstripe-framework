<?php

namespace SilverStripe\Control\RSS;

use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ViewableData;
use BadMethodCallException;

/**
 * RSSFeed_Entry class
 *
 * This class is used for entries of an RSS feed.
 *
 * @see RSSFeed
 */
class RSSFeed_Entry extends ViewableData
{
    /**
     * The object that represents the item, it contains all the data.
     *
     * @var mixed
     */
    protected $failover;

    /**
     * Name of the title field of feed entries
     *
     * @var string
     */
    protected $titleField;

    /**
     * Name of the description field of feed entries
     *
     * @var string
     */
    protected $descriptionField;

    /**
     * Name of the author field of feed entries
     *
     * @var string
     */
    protected $authorField;

    /**
     * Create a new RSSFeed entry.
     * @param ViewableData $entry
     * @param string $titleField
     * @param string $descriptionField
     * @param string $authorField
     */
    public function __construct($entry, $titleField, $descriptionField, $authorField)
    {
        $this->failover = $entry;
        $this->titleField = $titleField;
        $this->descriptionField = $descriptionField;
        $this->authorField = $authorField;

        parent::__construct();
    }

    /**
     * Get the description of this entry
     *
     * @return DBField Returns the description of the entry.
     */
    public function Title()
    {
        return $this->rssField($this->titleField);
    }

    /**
     * Get the description of this entry
     *
     * @return DBField Returns the description of the entry.
     */
    public function Description()
    {
        $description = $this->rssField($this->descriptionField);

        // HTML fields need links re-written
        if ($description instanceof DBHTMLText) {
            return $description->obj('AbsoluteLinks');
        }

        return $description;
    }

    /**
     * Get the author of this entry
     *
     * @return DBField Returns the author of the entry.
     */
    public function Author()
    {
        return $this->rssField($this->authorField);
    }

    /**
     * Return the safely casted field
     *
     * @param string $fieldName Name of field
     * @return DBField
     */
    public function rssField($fieldName)
    {
        if ($fieldName) {
            return $this->failover->obj($fieldName);
        }
        return null;
    }

    /**
     * Get a link to this entry
     *
     * @return string Returns the URL of this entry
     * @throws BadMethodCallException
     */
    public function AbsoluteLink()
    {
        if ($this->failover->hasMethod('AbsoluteLink')) {
            return $this->failover->AbsoluteLink();
        } else {
            if ($this->failover->hasMethod('Link')) {
                return Director::absoluteURL((string) $this->failover->Link());
            }
        }

        throw new BadMethodCallException(
            get_class($this->failover) . " object has neither an AbsoluteLink nor a Link method." . " Can't put a link in the RSS feed",
            E_USER_WARNING
        );
    }
}
