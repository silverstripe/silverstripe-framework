<?php
/**
 * @package framework
 * @subpackage tests
 *
 * @todo Test the validation method php()
 */
class RequiredFieldsTest extends SapphireTest {

	public function testConstructingWithArray() {
		//can we construct with an array?
		$fields = array(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		);
		$requiredFields = new RequiredFields($fields);
		//check the fields and the array match
		$this->assertEquals(
			$fields,
			$requiredFields->getRequired(),
			"Failed to set the required fields using an array"
		);
	}

	public function testConstructingWithArguments() {
		//can we construct with arguments?
		$requiredFields = new RequiredFields(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		);
		//check the fields match
		$this->assertEquals(
			array(
				'Title',
				'Content',
				'Image',
				'AnotherField'
			),
			$requiredFields->getRequired(),
			"Failed to set the required fields using arguments"
		);
	}

	public function testRemoveValidation() {
		//can we remove all fields at once?
		$requiredFields = new RequiredFields(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		);
		$requiredFields->removeValidation();
		//check there are no required fields
		$this->assertEmpty(
			$requiredFields->getRequired(),
			"Failed to remove all the required fields using 'removeValidation()'"
		);
	}

	public function testRemoveRequiredField() {
		//set up the required fields
		$requiredFields = new RequiredFields(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		);
		//remove one
		$requiredFields->removeRequiredField('Content');
		//compare the arrays
		$this->assertEquals(
			array(
				'Title',
				'Image',
				'AnotherField'
			),
			$requiredFields->getRequired(),
			"Failed to remove the 'Content' field from required list"
		);
		//let's remove another
		$requiredFields->removeRequiredField('Title');
		$this->assertEquals(
			array(
				'Image',
				'AnotherField'
			),
			$requiredFields->getRequired(),
			"Failed to remove 'Title' field from required list"
		);
		//lets try to remove one that doesn't exist
		$requiredFields->removeRequiredField('DontExists');
		$this->assertEquals(
			array(
				'Image',
				'AnotherField'
			),
			$requiredFields->getRequired(),
			"Removing a non-existant field from required list altered the list of required fields"
		);
	}

	public function testAddRequiredField() {
		//set up the validator
		$requiredFields = new RequiredFields(
			'Title'
		);
		//add a field
		$requiredFields->addRequiredField('Content');
		//check it was added
		$this->assertEquals(
			array(
				'Title',
				'Content'
			),
			$requiredFields->getRequired(),
			"Failed to add a new field to the required list"
		);
		//add another for good measure
		$requiredFields->addRequiredField('Image');
		//check it was added
		$this->assertEquals(
			array(
				'Title',
				'Content',
				'Image'
			),
			$requiredFields->getRequired(),
			"Failed to add a second new field to the required list"
		);
		//remove a field
		$requiredFields->removeRequiredField('Title');
		//check it was removed
		$this->assertEquals(
			array(
				'Content',
				'Image'
			),
			$requiredFields->getRequired(),
			"Failed to remove 'Title' field from required list"
		);
		//add the same field back to check we can add and remove at will
		$requiredFields->addRequiredField('Title');
		//check it's in there
		$this->assertEquals(
			array(
				'Content',
				'Image',
				'Title'
			),
			$requiredFields->getRequired(),
			"Failed to add 'Title' back to the required field list"
		);
		//add a field that already exists (we can't have the same field twice, can we?)
		$requiredFields->addRequiredField('Content');
		//check the field wasn't added
		$this->assertEquals(
			array(
				'Content',
				'Image',
				'Title'
			),
			$requiredFields->getRequired(),
			"Adding a duplicate field to required field list had unexpected behaviour"
		);
	}

	public function testAppendRequiredFields() {
		//get the validator
		$requiredFields = new RequiredFields(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		);
		//create another validator with other fields
		$otherRequiredFields = new RequiredFields(array(
			'ExtraField1',
			'ExtraField2'
		));
		//append the new fields
		$requiredFields->appendRequiredFields($otherRequiredFields);
		//check they were added correctly
		$this->assertEquals(
			array(
				'Title',
				'Content',
				'Image',
				'AnotherField',
				'ExtraField1',
				'ExtraField2'
			),
			$requiredFields->getRequired(),
			"Merging of required fields failed to behave as expected"
		);
		// create the standard validator so we can check duplicates are ignored
		$otherRequiredFields = new RequiredFields(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		);
		//add the new validator
		$requiredFields->appendRequiredFields($otherRequiredFields);
		//check nothing was changed
		$this->assertEquals(
			array(
				'Title',
				'Content',
				'Image',
				'AnotherField',
				'ExtraField1',
				'ExtraField2'
			),
			$requiredFields->getRequired(),
			"Merging of required fields with duplicates failed to behave as expected"
		);
		//add some new fields and some old ones in a strange order
		$otherRequiredFields = new RequiredFields(
			'ExtraField3',
			'Title',
			'ExtraField4',
			'Image',
			'Content'
		);
		//add the new validator
		$requiredFields->appendRequiredFields($otherRequiredFields);
		//check that only the new fields were added
		$this->assertEquals(
			array(
				'Title',
				'Content',
				'Image',
				'AnotherField',
				'ExtraField1',
				'ExtraField2',
				'ExtraField3',
				'ExtraField4'
			),
			$requiredFields->getRequired(),
			"Merging of required fields with some duplicates in a muddled order failed to behave as expected"
		);
	}

	public function testFieldIsRequired() {
		//get the validator
		$requiredFields = new RequiredFields($fieldNames = array(
			'Title',
			'Content',
			'Image',
			'AnotherField'
		));

		foreach($fieldNames as $field) {
			$this->assertTrue(
				$requiredFields->fieldIsRequired($field),
				sprintf("Failed to find '%s' field in required list", $field)
			);
		}

		//add a new field
		$requiredFields->addRequiredField('ExtraField1');
		//check the new field is required
		$this->assertTrue(
			$requiredFields->fieldIsRequired('ExtraField1'),
			"Failed to find 'ExtraField1' field in required list after adding it to the list"
		);
		//check a non-existant field returns false
		$this->assertFalse(
			$requiredFields->fieldIsRequired('DoesntExist'),
			"Unexpectedly returned true for a non-existant field"
		);
	}

}
