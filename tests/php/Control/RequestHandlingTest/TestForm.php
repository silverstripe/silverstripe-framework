<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;

/**
 * Form for the test
 */
class TestForm extends Form implements TestOnly
{
    protected function buildRequestHandler()
    {
        return TestFormHandler::create($this);
    }
}
