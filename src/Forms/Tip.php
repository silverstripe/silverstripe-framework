<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;

/**
 * Represents a Tip which can be rendered alongside a form field in the front-end.
 * See the Tip component in the silverstripe/admin module.
 *
 * @package SilverStripe\Forms
 */
class Tip
{
    /**
     * These map to levels in the front-end Tip component
     */
    const IMPORTANCE_LEVELS = [
        'NORMAL' => 'normal',
        'HIGH' => 'high',
    ];

    const DEFAULT_ICON = 'lamp';

    const DEFAULT_IMPORTANCE_LEVEL = self::IMPORTANCE_LEVELS['NORMAL'];

    /**
     * @var string The icon that should be used on the Tip button
     */
    private $icon;

    /**
     * @var string How important the tip is (normal or high). Informs the color and description.
     */
    private $importance_level;

    /**
     * @var string The contents of the Tip UI
     */
    private $message;

    public function __construct(
        $message,
        $importance_level = self::DEFAULT_IMPORTANCE_LEVEL,
        $icon = self::DEFAULT_ICON
    )
    {
        if (!in_array($importance_level, self::IMPORTANCE_LEVELS)) {
            throw new InvalidArgumentException(
                'Provided $importance_level must be defined in Tip::IMPORTANCE_LEVELS'
            );
        }

        $this->message = $message;
        $this->icon = $icon;
        $this->importance_level = $importance_level;
    }

    /**
     * Outputs props to be passed to the front-end Tip component.
     *
     * @return array
     */
    public function getTipSchema()
    {
        return [
            'content' => $this->message,
            'icon' => $this->icon,
            'importance' => $this->importance_level,
        ];
    }

    /**
     * @return string
     */
    public function getImportanceLevel()
    {
        return $this->importance_level;
    }

    /**
     * @param string $importance_level
     */
    public function setImportanceLevel($importance_level)
    {
        if (!in_array($importance_level, self::IMPORTANCE_LEVELS)) {
            throw new InvalidArgumentException(
                'Provided $importance_level must be defined in Tip::IMPORTANCE_LEVELS'
            );
        }

        $this->importance_level = $importance_level;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
