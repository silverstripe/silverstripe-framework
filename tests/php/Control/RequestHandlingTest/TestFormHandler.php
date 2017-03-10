<?php


namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Forms\FormRequestHandler;

/**
 * Handler for
 * @see TestForm
 */
class TestFormHandler extends FormRequestHandler
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
        return $this->form->Fields()->dataFieldByName($request->param('FieldName'));
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
