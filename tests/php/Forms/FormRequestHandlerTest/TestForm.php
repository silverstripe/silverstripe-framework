<?php

namespace SilverStripe\Forms\Tests\FormRequestHandlerTest;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\Form;

class TestForm extends Form
{
    public function mySubmitOnForm()
    {
        return new HTTPResponse('success', 200);
    }
}
