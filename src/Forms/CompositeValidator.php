<?php

namespace SilverStripe\Forms;

use InvalidArgumentException;
use SilverStripe\ORM\ValidationResult;

/**
 * CompositeValidator can contain between 0 and many different types of Validators. Each Validator is itself still
 * responsible for Validating its form and generating its ValidationResult.
 *
 * Once each Validator has generated its ValidationResult, the CompositeValidator will combine these results into a
 * single ValidationResult. This single ValidationResult is what will be returned by the CompositeValidator.
 *
 * You can add Validators to the CompositeValidator in any DataObject by extending the getCMSCompositeValidator()
 * method:
 *
 * public function getCMSCompositeValidator(): CompositeValidator
 * {
 *   $compositeValidator = parent::getCMSCompositeValidator();
 *
 *   $compositeValidator->addValidator(RequiredFields::create(['MyRequiredField']));
 *
 *   return $compositeValidator
 * }
 *
 * Or by implementing the updateCMSCompositeValidator() method in a DataExtension:
 *
 * public function updateCMSCompositeValidator(CompositeValidator $compositeValidator): void
 * {
 *   $compositeValidator->addValidator(RequiredFields::create(['AdditionalContent']));
 * }
 */
class CompositeValidator extends Validator
{
    /**
     * @var array<Validator>
     */
    private $validators;

    /**
     * CompositeValidator constructor.
     *
     * @param array<Validator> $validators
     */
    public function __construct(array $validators = [])
    {
        $this->validators = array_values($validators ?? []);

        parent::__construct();
    }

    /**
     * Set the provided Form to the CompositeValidator and each Validator that has been added.
     *
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
     * @return CompositeValidator
     */
    public function addValidator(Validator $validator): CompositeValidator
    {
        $this->validators[] = $validator;

        return $this;
    }

    /**
     * Returns any errors there may be. This method considers the enabled status of the CompositeValidator as a whole
     * (exiting early if the Composite is disabled), as well as the enabled status of each individual Validator.
     *
     * @return ValidationResult
     */
    public function validate()
    {
        $this->resetResult();

        // This CompositeValidator has been disabled in full
        if (!$this->getEnabled()) {
            return $this->result;
        }

        foreach ($this->getValidators() as $validator) {
            // validate() will return a ValidationResult, and we will combine this with the result we already have
            $this->getResult()->combineAnd($validator->validate());
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
     * @return array<Validator>
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * Return all Validators that match a certain class name. EG: RequiredFields::class
     *
     * The keys for the return array match the keys in the unfiltered array. You cannot assume the keys will be
     * sequential or that the first key will be ZERO.
     *
     * @template T of Validator
     * @param class-string<T> $className
     * @return T[]
     */
    public function getValidatorsByType(string $className): array
    {
        $validators = [];

        foreach ($this->getValidators() as $key => $validator) {
            if (!$validator instanceof $className) {
                continue;
            }

            $validators[$key] = $validator;
        }

        return $validators;
    }

    /**
     * Remove all Validators that match a certain class name. EG: RequiredFields::class
     *
     * @param string $className
     * @return CompositeValidator
     */
    public function removeValidatorsByType(string $className): CompositeValidator
    {
        foreach ($this->getValidatorsByType($className) as $key => $validator) {
            $this->removeValidatorByKey($key);
        }

        return $this;
    }

    /**
     * Each Validator is aware of whether or not it can be cached. If even one Validator cannot be cached, then the
     * CompositeValidator as a whole also cannot be cached.
     *
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

    /**
     * @internal This method may be updated to public in the future. Let us know if you feel there's a use case for it
     * @param int $key
     * @return CompositeValidator
     */
    protected function removeValidatorByKey(int $key): CompositeValidator
    {
        if (!array_key_exists($key, $this->validators ?? [])) {
            throw new InvalidArgumentException(
                sprintf('Key "%s" does not exist in $validators array', $key)
            );
        }

        unset($this->validators[$key]);

        return $this;
    }
}
