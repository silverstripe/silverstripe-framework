<?php
/**
 * @package framework
 * @subpackage tests
 */
class FileFieldTest extends FunctionalTest {

	/**
	 * Test a valid upload of a required file in a form. Error is set to 0, as the upload went well
	 */
	public function testUploadRequiredFile() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldList(
				$fileField = new FileField('cv', 'Upload your CV')
			),
			new FieldList()
		);
		$fileFieldValue = array(
			'name' => 'aCV.txt',
			'type' => 'application/octet-stream',
			'tmp_name' => '/private/var/tmp/phpzTQbqP',
			'error' => 0,
			'size' => 3471
		);
		$fileField->setValue($fileFieldValue);

		$this->assertTrue(
			$form->validate()
		);
	}

	/**
	 * Test different scenarii for a failed upload : an error occured, no files where provided
	 */
	public function testUploadMissingRequiredFile() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldList(
				$fileField = new FileField('cv', 'Upload your CV')
			),
			new FieldList(),
			new RequiredFields('cv')
		);
		// All fields are filled but for some reason an error occured when uploading the file => fails
		$fileFieldValue = array(
			'name' => 'aCV.txt',
			'type' => 'application/octet-stream',
			'tmp_name' => '/private/var/tmp/phpzTQbqP',
			'error' => 1,
			'size' => 3471
		);
		$fileField->setValue($fileFieldValue);

		$this->assertFalse(
			$form->validate(),
			'An error occured when uploading a file, but the validator returned true'
		);

		// We pass an empty set of parameters for the uploaded file => fails
		$fileFieldValue = array();
		$fileField->setValue($fileFieldValue);

		$this->assertFalse(
			$form->validate(),
			'An empty array was passed as parameter for an uploaded file, but the validator returned true'
		);

		// We pass an null value for the uploaded file => fails
		$fileFieldValue = null;
		$fileField->setValue($fileFieldValue);

		$this->assertFalse(
			$form->validate(),
			'A null value was passed as parameter for an uploaded file, but the validator returned true'
		);
	}
}
