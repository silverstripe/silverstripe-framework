<?php

namespace SilverStripe\Forms\Tests\ValidatorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Validator;

class TestValidator extends Validator implements TestOnly
{

    /**
     * Requires a specific field for test purposes.
     *
     * @param array $data
     * @return null
     */
    public function php($data)
    {
        foreach ($data as $field => $data) {
            $this->validationError($field, 'error');
        }
    }
}
