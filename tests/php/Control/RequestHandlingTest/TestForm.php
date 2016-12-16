<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;

/**
 * Form for the test
 */
class TestForm extends Form implements TestOnly
{
    private static $url_handlers = array(
        'fields/$FieldName' => 'handleField',
        "POST " => "handleSubmission",
        "GET " => "handleGet",
    );

    // These are a different case from those in url_handlers to confirm that it's all case-insensitive
    private static $allowed_actions = array(
        'handlesubmission',
        'handlefield',
        'handleget',
    );

    public function handleField($request)
    {
        return $this->Fields()->dataFieldByName($request->param('FieldName'));
    }

    public function handleSubmission($request)
    {
        return "Form posted";
    }

    public function handleGet($request)
    {
        return "Get request on form";
    }
}
