<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\Tests\GridField\GridField_URLHandlerTest\TestController;

/**
 * Test the API for creating GridField_URLHandler compeonnts
 */
class GridField_URLHandlerTest extends FunctionalTest
{

    protected static $extra_controllers = [
        TestController::class,
    ];

    public function testFormSubmission()
    {
        $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/showform");
        $formResult = $this->submitForm('Form_Form', 'action_doAction', array('Test' => 'foo bar'));
        $this->assertEquals("Submitted foo bar to component", $formResult->getBody());
    }

    public function testNestedRequestHandlerFormSubmission()
    {
        $result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/item/3/showform");
        $formResult = $this->submitForm('Form_Form', 'action_doAction', array('Test' => 'foo bar'));
        $this->assertEquals("Submitted foo bar to item #3", $formResult->getBody());
    }

    public function testURL()
    {
        $result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/testpage");
        $this->assertEquals("Test page for component", $result->getBody());
    }

    public function testNestedRequestHandlerURL()
    {
        $result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/item/5/testpage");
        $this->assertEquals("Test page for item #5", $result->getBody());
    }
}
