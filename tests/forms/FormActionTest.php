<?php

/**
 * @package framework
 * @subpackage tests
 */
class FormActionTest extends SapphireTest {

	protected static $build_db_each_test = false;
	
	public function testGetField() {
		$formAction = new FormAction('test');
		$this->assertContains('type="submit"',  $formAction->getAttributesHTML());

		$formAction->setAttribute('src', 'file.png');
		$this->assertContains('type="image"', $formAction->getAttributesHTML());
	}
}
