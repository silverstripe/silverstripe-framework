<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldGroup;

class FieldGroupTest extends SapphireTest {

	public function testMessagesInsideNestedCompositeFields() {
		$fieldGroup = new FieldGroup(
			new CompositeField(
				$textField = new TextField('TestField', 'Test Field'),
				$emailField = new EmailField('TestEmailField', 'Test Email Field')
			)
		);

		$textField->setError('Test error message', 'warning');
		$emailField->setError('Test error message', 'error');

		$this->assertEquals('Test error message,  Test error message.', $fieldGroup->Message());
		$this->assertEquals('warning.  error', $fieldGroup->MessageType());
	}

}
