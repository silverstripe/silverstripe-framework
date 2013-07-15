<?php
/**
 * @package framework
 * @subpackage tests
 */
class RequiredFieldsTest extends SapphireTest {

	public function testRemoveRequiredField() {
		//set up the required fields
		$requiredFields = new RequiredFields(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		);
		$requiredFields->removeRequiredField('Content');
		$this->assertEquals(array(
			'Title',
			'Image',
			'AnotherField'
		), array_values($requiredFields->getRequired()));
	}

}
