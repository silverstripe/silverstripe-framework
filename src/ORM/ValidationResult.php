<?php

namespace SilverStripe\ORM;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;

/**
 * A class that combined as a boolean result with an optional list of error messages.
 * This is used for returning validation results from validators
 *
 * Each message can have a code or field which will uniquely identify that message. However,
 * messages can be stored without a field or message as an "overall" message.
 */
class ValidationResult
{
    use Injectable;

    /**
     * Standard "error" type
     */
    const TYPE_ERROR = 'error';

    /**
     * Standard "good" message type
     */
    const TYPE_GOOD = 'good';

    /**
     * Non-error message type.
     */
    const TYPE_INFO = 'info';

    /**
     * Warning message type
     */
    const TYPE_WARNING = 'warning';

    /**
     * Message type is html
     */
    const CAST_HTML = 'html';

    /**
     * Message type is plain text
     */
    const CAST_TEXT = 'text';

    /**
     * Is the result valid or not.
     * Note that there can be non-error messages in the list.
     *
     * @var bool
     */
    protected $isValid = true;

    /**
     * List of messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Record an error against this validation result,
     *
     * @param string $message     The message string.
     * @param string $messageType Passed as a CSS class to the form, so other values can be used if desired.
     * Standard types are defined by the TYPE_ constant definitions.
     * @param string $code        A codename for this error. Only one message per codename will be added.
     *                            This can be usedful for ensuring no duplicate messages
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     * @return $this
     */
    public function addError($message, $messageType = ValidationResult::TYPE_ERROR, $code = null, $cast = ValidationResult::CAST_TEXT)
    {
        return $this->addFieldError(null, $message, $messageType, $code, $cast);
    }

    /**
     * Record an error against this validation result,
     *
     * @param string $fieldName   The field to link the message to.  If omitted; a form-wide message is assumed.
     * @param string $message     The message string.
     * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
     *                            class to the form, so other values can be used if desired.
     * @param string $code        A codename for this error. Only one message per codename will be added.
     *                            This can be usedful for ensuring no duplicate messages
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     * @return $this
     */
    public function addFieldError(
        $fieldName,
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $code = null,
        $cast = ValidationResult::CAST_TEXT
    ) {
        $this->isValid = false;
        return $this->addFieldMessage($fieldName, $message, $messageType, $code, $cast);
    }

    /**
     * Add a message to this ValidationResult without necessarily marking it as an error
     *
     * @param string $message     The message string.
     * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
     *                            class to the form, so other values can be used if desired.
     * @param string $code        A codename for this error. Only one message per codename will be added.
     *                            This can be usedful for ensuring no duplicate messages
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     * @return $this
     */
    public function addMessage($message, $messageType = ValidationResult::TYPE_ERROR, $code = null, $cast = ValidationResult::CAST_TEXT)
    {
        return $this->addFieldMessage(null, $message, $messageType, $code, $cast);
    }

    /**
     * Add a message to this ValidationResult without necessarily marking it as an error
     *
     * @param string $fieldName   The field to link the message to.  If omitted; a form-wide message is assumed.
     * @param string $message     The message string.
     * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
     *                            class to the form, so other values can be used if desired.
     * @param string $code        A codename for this error. Only one message per codename will be added.
     *                            This can be usedful for ensuring no duplicate messages
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     * @return $this
     */
    public function addFieldMessage(
        $fieldName,
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $code = null,
        $cast = ValidationResult::CAST_TEXT
    ) {
        if ($code && is_numeric($code)) {
            throw new InvalidArgumentException("Don't use a numeric code '$code'.  Use a string.");
        }
        if (is_bool($cast)) {
            $cast = $cast ? ValidationResult::CAST_TEXT : ValidationResult::CAST_HTML;
        }
        $metadata = [
            'message' => $message,
            'fieldName' => $fieldName,
            'messageType' => $messageType,
            'messageCast' => $cast,
        ];

        if ($code) {
            $this->messages[$code] = $metadata;
        } else {
            $this->messages[] = $metadata;
        }

        return $this;
    }

    /**
     * Returns true if the result is valid.
     * @return boolean
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * Return the full error meta-data, suitable for combining with another ValidationResult.
     *
     * @return array Array of messages, where each item is an array of data for that message.
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Combine this Validation Result with the ValidationResult given in other.
     * It will be valid if both this and the other result are valid.
     * This object will be modified to contain the new validation information.
     *
     * @param ValidationResult $other the validation result object to combine
     * @return $this
     */
    public function combineAnd(ValidationResult $other)
    {
        $this->isValid = $this->isValid && $other->isValid();
        $this->messages = array_merge($this->messages, $other->getMessages());
        return $this;
    }

    public function __serialize(): array
    {
        return [
            'messages' => $this->messages,
            'isValid' => $this->isValid()
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->messages = $data['messages'];
        $this->isValid = $data['isValid'];
    }
}
