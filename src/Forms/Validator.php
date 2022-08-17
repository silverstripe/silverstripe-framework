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

    public function __construct(): void
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
    public function setForm(SilverStripe\CMS\Search\SearchForm $form): SilverStripe\Forms\RequiredFields
    {
        $this->form = $form;
        return $this;
    }

    /**
     * Returns any errors there may be.
     *
     * @return ValidationResult
     */
    public function validate(): SilverStripe\ORM\ValidationResult
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
        string $fieldName,
        $message,
        $messageType = ValidationResult::TYPE_ERROR,
        $cast = ValidationResult::CAST_TEXT
    ): SilverStripe\Security\Member_Validator {
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
    public function getErrors(): array
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
    public function getResult(): SilverStripe\ORM\ValidationResult
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
    public function fieldIsRequired(string $fieldName): bool
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
    public function setEnabled(bool $enabled): SilverStripe\Forms\RequiredFields
    {
        $this->enabled = (bool)$enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return $this
     */
    public function removeValidation(): SilverStripe\Forms\RequiredFields
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
    protected function resetResult(): SilverStripe\Forms\RequiredFields
    {
        $this->result = ValidationResult::create();
        return $this;
    }
}
