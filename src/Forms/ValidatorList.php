<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\ValidationResult;

/**
 * Class ValidatorList
 *
 * @package SilverStripe\Forms
 */
class ValidatorList extends Validator
{
    /**
     * @var ArrayList|Validator[]
     */
    private $validators;

    public function __construct()
    {
        $this->validators = ArrayList::create();

        parent::__construct();
    }

    /**
     * @param Form $form
     * @return Validator
     */
    public function setForm($form)
    {
        foreach ($this->getValidators() as $validator) {
            $validator->setForm($form);
        }

        return parent::setForm($form);
    }

    /**
     * @param Validator $validator
     * @return ValidatorList
     */
    public function addValidator(Validator $validator): ValidatorList
    {
        $this->getValidators()->add($validator);

        return $this;
    }

    /**
     * Returns any errors there may be. This method considers the enabled status of the ValidatorList as a whole
     * (exiting early if the List is disabled), as well as the enabled status of each individual Validator.
     *
     * @return ValidationResult
     */
    public function validate()
    {
        $this->resetResult();

        // This ValidatorList has been disabled in full
        if (!$this->getEnabled()) {
            return $this->result;
        }

        $data = $this->form->getData();

        foreach ($this->getValidators() as $validator) {
            // Reset the validation results for this Validator
            $validator->resetResult();

            // This Validator has been disabled, so skip it
            if (!$validator->getEnabled()) {
                continue;
            }

            // Run validation, and exit early if it's valid
            if ($validator->php($data)) {
                continue;
            }

            // Validation result was invalid. Combine our ValidationResult messages
            $this->getResult()->combineAnd($validator->getResult());
        }

        return $this->result;
    }

    /**
     * Note: The existing implementations for the php() method (@see RequiredFields) does not check whether the
     * Validator is enabled or not, and it also does not reset the validation result - so, neither does this.
     *
     * @param array $data
     * @return bool
     */
    public function php($data)
    {
        foreach ($this->getValidators() as $validator) {
            // Run validation, and exit early if it's valid
            if ($validator->php($data)) {
                continue;
            }

            // Validation result was invalid. Combine our ValidationResult messages
            $this->getResult()->combineAnd($validator->getResult());
        }

        // After collating results, return whether or not everything was valid
        return $this->getResult()->isValid();
    }

    /**
     * Returns whether the field in question is required. This will usually display '*' next to the
     * field.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function fieldIsRequired($fieldName)
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator->fieldIsRequired($fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ArrayList|Validator[]
     */
    public function getValidators(): ArrayList
    {
        return $this->validators;
    }

    /**
     * @param string $className
     * @return ArrayList|Validator[]
     */
    public function getValidatorsByType(string $className): ArrayList
    {
        $validators = ArrayList::create();

        foreach ($this->getValidators() as $validator) {
            if (!$validator instanceof $className) {
                continue;
            }

            $validators->add($validator);
        }

        return $validators;
    }

    /**
     * @param string $className
     * @return ValidatorList
     */
    public function removeValidatorsByType(string $className): ValidatorList
    {
        foreach ($this->getValidators() as $validator) {
            if (!$validator instanceof $className) {
                continue;
            }

            $this->getValidators()->remove($validator);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function canBeCached(): bool
    {
        foreach ($this->getValidators() as $validator) {
            if (!$validator->canBeCached()) {
                return false;
            }
        }

        return true;
    }
}
