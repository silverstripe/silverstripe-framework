<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ValidationResult;

/**
 * This validation class handles all form and custom form validation through the use of Required
 * fields. It relies on javascript for client-side validation, and marking fields after server-side
 * validation. It acts as a visitor to individual form fields.
 */
abstract class Validator
{
    use Injectable;
    use Configurable;
    use Extensible;

    public function __construct()
    {
        $this->resetResult();
    }

    /**
     * @var Form $form
     */
    protected $form;

    /**
     * @var ValidationResult $result
     */
    protected $result;

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @param Form $form
     * @return $this
     */
    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * Returns any errors there may be.
     *
     * @return ValidationResult
     */
    public function validate()
    {
        $this->resetResult();
        if ($this->getEnabled()) {
            $this->php($this->form->getData());
        }
        return $this->result;
    }

    /**
     * Callback to register an error on a field (Called from implementations of
     * {@link FormField::validate}). The optional error message type parameter is loaded into the
     * HTML class attribute.
     *
     * See {@link getErrors()} for details.
     *
     * @param string $fieldName Field name for this error
     * @param string $message The message string
     * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
     *                            class to the form, so other values can be used if desired.
     * @param string|bool $cast Cast type; One of the CAST_ constant definitions.
     * Bool values will be treated as plain text flag.
     * @return $this
     */
    public function validationError(
        $fieldName,
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $cast = ValidationResult::CAST_TEXT
    ) {
        $this->result->addFieldError($fieldName, $message, $messageType, null, $cast);
        return $this;
    }

    /**
     * Returns all errors found by a previous call to {@link validate()}. The returned array has a
     * structure resembling:
     *
     * <code>
     *     array(
     *         'fieldName' => '[form field name]',
     *         'message' => '[validation error message]',
     *         'messageType' => '[bad|message|validation|required]',
     *         'messageCast' => '[text|html]'
     *     )
     * </code>
     *
     * @return null|array
     */
    public function getErrors()
    {
        if ($this->result) {
            return $this->result->getMessages();
        }
        return null;
    }

    /**
     * Get last validation result
     *
     * @return ValidationResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns whether the field in question is required. This will usually display '*' next to the
     * field. The base implementation always returns false.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function fieldIsRequired($fieldName)
    {
        return false;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    abstract public function php($data);

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return $this
     */
    public function removeValidation()
    {
        $this->setEnabled(false);
        $this->resetResult();
        return $this;
    }

    /**
     * When Validators are set on the form, it can affect whether or not the form cannot be cached.
     *
     * @see RequiredFields for an example of when you might be able to cache your form.
     *
     * @return bool
     */
    public function canBeCached(): bool
    {
        return false;
    }

    /**
     * Clear current result
     *
     * @return $this
     */
    protected function resetResult()
    {
        $this->result = ValidationResult::create();
        return $this;
    }
}
