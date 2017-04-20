<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

/**
 * Form field for the test
 */
class SubclassedFormField extends TestFormField
{

    private static $allowed_actions = array('customSomething');

    // We have some url_handlers defined that override RequestHandlingTest_FormField handlers.
    // We will confirm that the url_handlers inherit.
    private static $url_handlers = array(
        'something' => 'customSomething',
    );


    public function customSomething()
    {
        return "customSomething";
    }
}
