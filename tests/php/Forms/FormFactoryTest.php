<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\Tests\FormFactoryTest\TestController;
use SilverStripe\Forms\Tests\FormFactoryTest\TestObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Versioned\Versioned;

class FormFactoryTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    protected static $fixture_file = 'FormFactoryTest.yml';

    public static function getExtraDataObjects()
    {
        // Prevent setup breaking if versioned module absent
        if (class_exists(Versioned::class)) {
            return parent::getExtraDataObjects();
        }
        return [];
    }

    public function setUp()
    {
        parent::setUp();

        // Note: Soft support for versioned module optionality
        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped('FormFactoryTest requires the Versioned extension');
        }
    }

    /**
     * Test versioned form
     */
    public function testVersionedForm()
    {
        $controller = new TestController();
        $form = $controller->Form();

        // Check formfields
        $this->assertInstanceOf(TextField::class, $form->Fields()->fieldByName('Title'));
        $this->assertInstanceOf(HiddenField::class, $form->Fields()->fieldByName('ID'));
        $this->assertInstanceOf(HiddenField::class, $form->Fields()->fieldByName('SecurityID'));

        // Check preview link
        /** @var LiteralField $previewLink */
        $previewLink = $form->Fields()->fieldByName('PreviewLink');
        $this->assertInstanceOf(LiteralField::class, $previewLink);
        $this->assertEquals(
            '<a href="FormFactoryTest_TestController/preview/" rel="external" target="_blank">Preview</a>',
            $previewLink->getContent()
        );

        // Check actions
        $this->assertInstanceOf(FormAction::class, $form->Actions()->fieldByName('action_save'));
        $this->assertInstanceOf(FormAction::class, $form->Actions()->fieldByName('action_publish'));
        $this->assertTrue($controller->hasAction('publish'));
    }

    /**
     * Removing versioning from an object should result in a simpler form
     */
    public function testBasicForm()
    {
        TestObject::remove_extension(Versioned::class);
        $controller = new TestController();
        $form = $controller->Form();

        // Check formfields
        $this->assertInstanceOf(TextField::class, $form->Fields()->fieldByName('Title'));
        $this->assertNull($form->Fields()->fieldByName('PreviewLink'));

        // Check actions
        $this->assertInstanceOf(FormAction::class, $form->Actions()->fieldByName('action_save'));
        $this->assertNull($form->Actions()->fieldByName('action_publish'));
    }
}
