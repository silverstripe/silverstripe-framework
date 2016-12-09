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
