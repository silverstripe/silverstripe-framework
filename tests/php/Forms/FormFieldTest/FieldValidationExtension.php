<?php

namespace SilverStripe\Forms\Tests\FormFieldTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Validator;

class FieldValidationExtension extends Extension implements TestOnly
{
    protected bool $excludeFromValidation = false;

    protected bool $triggerTestValidationError = false;

    public function updateValidationResult(bool &$result, Validator $validator)
    {
        if ($this->excludeFromValidation) {
            $result = true;
            return;
        }

        if ($this->triggerTestValidationError) {
            $result = false;
            $validator->validationError($this->owner->getName(), 'A test error message');
            return;
        }
    }

    public function setExcludeFromValidation(bool $exclude)
    {
        $this->excludeFromValidation = $exclude;
    }

    public function setTriggerTestValidationError(bool $triggerTestValidationError)
    {
        $this->triggerTestValidationError = $triggerTestValidationError;
    }
}
