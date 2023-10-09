<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\ViewableData;

/**
 * Form component which contains a castable message
 *
 * @mixin ViewableData
 */
trait FormMessage
{

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var string
     */
    protected $messageType = '';

    /**
     * Casting for message
     *
     * @var string
     */
    protected $messageCast = null;

    /**
     * Returns the field message, used by form validation.
     *
     * Use {@link setError()} to set this property.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the field message type.
     *
     * Arbitrary value which is mostly used for CSS classes in the rendered HTML, e.g "required".
     *
     * Use {@link setError()} to set this property.
     *
     * @return string
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * Casting type for this message. Will be 'text' or 'html'
     *
     * @return string
     */
    public function getMessageCast()
    {
        return $this->messageCast;
    }

    /**
     * Sets the error message to be displayed on the form field.
     *
     * Allows HTML content, so remember to use Convert::raw2xml().
     *
     * @param string $message Message string
     * @param string $messageType Message type
     * @param string $messageCast
     * @return $this
     */
    public function setMessage(
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $messageCast = ValidationResult::CAST_TEXT
    ) {
        if (!in_array($messageCast, [ValidationResult::CAST_TEXT, ValidationResult::CAST_HTML])) {
            throw new InvalidArgumentException("Invalid message cast type");
        }
        $this->message = $message;
        $this->messageType = $messageType;
        $this->messageCast = $messageCast;
        return $this;
    }

    /**
     * Appends a message to the existing message if the types and casts match.
     * If either is different, the $force argument determines the behaviour.
     *
     * Note: to prevent duplicates, we check for the $message string in the existing message.
     * If the existing message contains $message as a substring, it won't be added.
     *
     * @param bool $force if true, and the new message cannot be appended to the existing one, the existing message will be overridden.
     * @throws InvalidArgumentException if $force is false and the messages can't be merged because of a mismatched type or cast.
     */
    public function appendMessage(
        string $message,
        string $messageType = ValidationResult::TYPE_ERROR,
        string $messageCast = ValidationResult::CAST_TEXT,
        bool $force = false,
    ): static {
        if (empty($message)) {
            return $this;
        }

        if (empty($this->message)) {
            return $this->setMessage($message, $messageType, $messageCast);
        }

        $canBeMerged = ($messageType === $this->getMessageType() && $messageCast === $this->getMessageCast());

        if (!$canBeMerged && !$force) {
            throw new InvalidArgumentException(
                sprintf(
                    "Couldn't append message of type %s and cast %s to existing message of type %s and cast %s",
                    $messageType,
                    $messageCast,
                    $this->getMessageType(),
                    $this->getMessageCast(),
                )
            );
        }

        // Checks that the exact message string is not already contained before appending
        $messageContainsString = strpos($this->message, $message) !== false;
        if ($canBeMerged && $messageContainsString) {
            return $this;
        }

        if ($canBeMerged) {
            $separator = $messageCast === ValidationResult::CAST_HTML ? '<br />' : PHP_EOL;
            $message = $this->message . $separator . $message;
        }

        return $this->setMessage($message, $messageType, $messageCast);
    }

    /**
     * Get casting helper for message cast, or null if not known
     *
     * @return string
     */
    protected function getMessageCastingHelper()
    {
        switch ($this->getMessageCast()) {
            case ValidationResult::CAST_TEXT:
                return 'Text';
            case ValidationResult::CAST_HTML:
                return 'HTMLFragment';
            default:
                return null;
        }
    }

    /**
     * Get form schema encoded message
     *
     * @return array|null Message in array format, or null if no message
     */
    public function getSchemaMessage()
    {
        $message = $this->getMessage();
        if (!$message) {
            return null;
        }
        // Form schema messages treat simple strings as plain text, so nest for html messages
        if ($this->getMessageCast() === ValidationResult::CAST_HTML) {
            $message = ['html' => $message];
        }
        return [
            'value' => $message,
            'type' => $this->getMessageType(),
        ];
    }
}
