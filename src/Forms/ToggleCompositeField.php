<?php

namespace SilverStripe\Forms;

use SilverStripe\View\Requirements;

/**
 * Allows visibility of a group of fields to be toggled.
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 */
class ToggleCompositeField extends CompositeField
{
    /**
     * @var bool
     */
    protected $startClosed = true;

    /**
     * @var int
     */
    protected $headingLevel = 3;

    /**
     * @inheritdoc
     *
     * @param string $name
     * @param string $title
     * @param array|FieldList $children
     */
    public function __construct($name, $title, $children)
    {
        parent::__construct($children);
        $this->setName($name);
        $this->setTitle($title);
    }

    /**
     * @inheritdoc
     *
     * @param array $properties
     * @return string
     */
    public function FieldHolder($properties = [])
    {
        $context = $this;

        if (count($properties ?? [])) {
            $context = $this->customise($properties);
        }

        return $context->renderWith($this->getTemplates());
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = [
            'id' => $this->ID(),
            'class' => $this->extraClass(),
        ];

        if ($this->getStartClosed()) {
            $attributes['class'] .= ' ss-toggle ss-toggle-start-closed';
        } else {
            $attributes['class'] .= ' ss-toggle';
        }

        return array_merge(
            $this->attributes,
            $attributes
        );
    }

    /**
     * @return bool
     */
    public function getStartClosed()
    {
        return $this->startClosed;
    }

    /**
     * Controls whether the field is open or closed by default. By default the field is closed.
     *
     * @param bool $startClosed
     *
     * @return $this
     */
    public function setStartClosed($startClosed)
    {
        $this->startClosed = (bool) $startClosed;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeadingLevel()
    {
        return $this->headingLevel;
    }

    /**
     * @param int $headingLevel
     *
     * @return $this
     */
    public function setHeadingLevel($headingLevel)
    {
        $this->headingLevel = (int) $headingLevel;

        return $this;
    }
}
