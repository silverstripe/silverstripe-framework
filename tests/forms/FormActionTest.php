<?php

/**
 * @package framework
 * @subpackage tests
 */
class FormActionTest extends SapphireTest {
	
	public function testGetField() {
		$formAction = new FormAction('test');
		$this->assertContains('type="submit"',  $formAction->AttributesHTML());

		$formAction->setAttribute('src', 'file.png');
		$this->assertContains('type="image"', $formAction->AttributesHTML());
	}
}