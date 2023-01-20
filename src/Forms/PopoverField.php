<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;

/**
 * Popup form action menu for "more options"
 *
 * Only works with react forms at the moment
 */
class PopoverField extends FieldGroup
{
    private static $cast = [
        'PopoverTitle' => 'HTMLText'
    ];

    /**
     * Use custom react component
     *
     * @var string
     */
    protected $schemaComponent = 'PopoverField';

    /**
     * Optional title on popup box
     *
     * @var string
     */
    protected $popoverTitle = null;

    protected $inputType = null;

    /**
     * Placement of the popup box, relative to the element triggering it.
     * Valid values: bottom, top, left, right.
     *
     * @var string
     */
    protected $placement = 'bottom';

    /**
     * Tooltip to put on button
     *
     * @var string
     */
    protected $buttonTooltip = null;

    /**
     * Get popup title
     *
     * @return string
     */
    public function getPopoverTitle()
    {
        return $this->popoverTitle;
    }

    /**
     * Set popup title
     *
     * @param string $popoverTitle
     * @return $this
     */
    public function setPopoverTitle($popoverTitle)
    {
        $this->popoverTitle = $popoverTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getButtonTooltip()
    {
        return $this->buttonTooltip;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setButtonTooltip($text)
    {
        $this->buttonTooltip = $text;
        return $this;
    }

    /**
     * Get popup placement
     *
     * @return string
     */
    public function getPlacement()
    {
        return $this->placement;
    }

    public function setPlacement($placement)
    {
        $valid = ['top', 'right', 'bottom', 'left'];

        if (!in_array($placement, $valid ?? [])) {
            throw new InvalidArgumentException(
                'Invalid placement value. Valid: top, left, bottom, right'
            );
        }

        $this->placement = $placement;

        return $this;
    }

    public function getSchemaDataDefaults()
    {
        $schema = parent::getSchemaDataDefaults();

        $schema['data']['popoverTitle'] = $this->getPopoverTitle();
        $schema['data']['placement'] = $this->getPlacement();
        $schema['data']['buttonTooltip'] = $this->getButtonTooltip();

        return $schema;
    }
}
