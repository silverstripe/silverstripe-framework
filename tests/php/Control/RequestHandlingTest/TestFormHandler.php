<?php


namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\FormRequestHandler;

/**
 * Handler for
 * @see TestForm
 */
class TestFormHandler extends FormRequestHandler
{
    private static $url_handlers = [
        'fields/$FieldName' => 'handleField',
        "POST /" => "handleSubmission",
        "GET /" => "handleGet",
    ];

    // These are a different case from those in url_handlers to confirm that it's all case-insensitive
    private static $allowed_actions = [
        'handlesubmission',
        'handlefield',
        'handleget',
    ];

    public function handleField($request)
    {
        return $this->form->Fields()->dataFieldByName($request->param('FieldName'));
    }

    public function handleSubmission(HTTPRequest $request): HTTPResponse
    {
        return HTTPResponse::create()->setBody("Form posted");
    }

    public function handleGet(HTTPRequest $request): HTTPResponse
    {
        return HTTPResponse::create()->setBody("Get request on form");
    }
}
