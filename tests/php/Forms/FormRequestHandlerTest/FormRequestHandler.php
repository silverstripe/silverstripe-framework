<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests\FormRequestHandlerTest;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\FormRequestHandler;

class TestFormRequestHandler extends FormRequestHandler
{
    public function mySubmitOnFormHandler()
    {
        return new HTTPResponse('success', 200);
    }
}
