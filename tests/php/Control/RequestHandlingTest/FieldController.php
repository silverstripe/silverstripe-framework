<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Tests\RequestHandlingTest\HandlingField;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * Controller for the test
 */
class FieldController extends Controller implements TestOnly
{
    private static $url_segment = 'FieldController';

    private static $allowed_actions = array('TestForm');

    public function TestForm()
    {
        return new Form(
            $this,
            "TestForm",
            new FieldList(
                new HandlingField("MyField")
            ),
            new FieldList(
                new FormAction("myAction")
            )
        );
    }
}
