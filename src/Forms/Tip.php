<?php
declare(strict_types=1);

namespace SilverStripe\Forms;

use InvalidArgumentException;

/**
 * Represents a Tip which can be rendered alongside a form field in the front-end.
 * See the Tip component in the silverstripe/admin module.
 */
class Tip
{
    /**
     * These map to levels in the front-end Tip component
     */
    public const IMPORTANCE_LEVELS = [
        'NORMAL' => 'normal',
        'HIGH' => 'high',
    ];

    private const DEFAULT_ICON = 'lamp';

    private const DEFAULT_IMPORTANCE_LEVEL = Tip::IMPORTANCE_LEVELS['NORMAL'];

    /**
     * @var string The icon that should be used on the Tip button
     */
    private $icon;

    /**
     * @var string How important the tip is (normal or high). Informs the color and description.
     */
    private $importance_level;

    /**
     * @var string The message to display in the tip
     */
    private $message;

    /**
     * @param string $message The message to display in the tip
     * @param string $importance_level How important the tip is (normal or high). Informs the color and description.
     * @param string $icon The icon that should be used on the Tip button
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $message,
        string $importance_level = Tip::DEFAULT_IMPORTANCE_LEVEL,
        string $icon = Tip::DEFAULT_ICON
    ) {
        $this->setMessage($message);
        $this->setIcon($icon);
        $this->setImportanceLevel($importance_level);
    }

    /**
     * Outputs props to be passed to the front-end Tip component.
     *
     * @return array
     */
    public function getTipSchema(): array
    {
        return [
            'content' => $this->getMessage(),
            'icon' => $this->getIcon(),
            'importance' => $this->getImportanceLevel(),
        ];
    }

    /**
     * @return string
     */
    public function getImportanceLevel(): string
    {
        return $this->importance_level;
    }

    /**
     * @param string $importance_level
     * @return Tip
     * @throws InvalidArgumentException
     */
    public function setImportanceLevel(string $importance_level): Tip
    {
        if (!in_array($importance_level, Tip::IMPORTANCE_LEVELS ?? [])) {
            throw new InvalidArgumentException(
                'Provided importance level must be defined in Tip::IMPORTANCE_LEVELS'
            );
        }

        $this->importance_level = $importance_level;

        return $this;
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
     * @return Tip
     */
    public function setIcon(string $icon): Tip
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return Tip
     */
    public function setMessage(string $message): Tip
    {
        $this->message = $message;

        return $this;
    }
}
