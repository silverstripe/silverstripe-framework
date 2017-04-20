<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FormField;

/**
 * Form field for the test
 */
class TestFormField extends FormField implements TestOnly
{
    private static $url_handlers = array(
        "POST " => "handleInPlaceEdit",
        '' => 'handleField',
        '$Action' => '$Action',
    );

    // These contain uppercase letters to test that allowed_actions doesn't need to be all lowercase
    private static $allowed_actions = array(
        'TEST',
        'handleField',
        'handleInPLACEEDIT',
    );

    public function test()
    {
        return "Test method on $this->name";
    }

    public function handleField()
    {
        return "$this->name requested";
    }

    public function handleInPlaceEdit($request)
    {
        return "$this->name posted, update to " . $request->postVar($this->name);
    }
}
