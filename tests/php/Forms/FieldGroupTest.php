<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldGroup;

class FieldGroupTest extends SapphireTest
{

    public function testMessagesInsideNestedCompositeFields()
    {
        $fieldGroup = new FieldGroup(
            new CompositeField(
                $textField = new TextField('TestField', 'Test Field'),
                $emailField = new EmailField('TestEmailField', 'Test Email Field')
            )
        );

        $textField->setMessage('Test error message', 'error');
        $emailField->setMessage('Test error warning', 'warning');

        $this->assertEquals('Test error message, Test error warning.', $fieldGroup->getMessage());
        $this->assertEquals('error', $fieldGroup->getMessageType());
    }
}
