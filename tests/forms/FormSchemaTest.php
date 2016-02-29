<?php

use SilverStripe\Forms\Schema\FormSchema;

class FormSchemaTest extends SapphireTest {

	public function testGetSchema() {
		$form = new Form(new Controller(), 'TestForm', new FieldList(), new FieldList());
		$formSchema = new FormSchema();
		$expected = [
			'name' => 'TestForm',
			'id' => null,
			'action' => null,
			'method' => '',
			'schema_url' => '',
			'attributes' => [
				'id' => 'Form_TestForm',
				'action' => 'Controller/TestForm',
				'method' => 'POST',
				'enctype' => 'application/x-www-form-urlencoded',
				'target' => null,
				'class' => ''
			],
			'data' => [],
			'fields' => [
				[
					'type' => "HiddenField",
					'component' => null,
					'id' => null,
					'holder_id' => null,
					'name' => 'SecurityID',
					'title' => 'Security ID',
					'source' => null,
					'extraClass' => 'hidden',
					'description' => null,
					'rightTitle' => null,
					'leftTitle' => null,
					'readOnly' => false,
					'disabled' => false,
					'customValidationMessage' => '',
					'attributes' => [],
					'data' => []
				],
			],
			'actions' => []
		];

		$schema = $formSchema->getSchema($form);
		$this->assertInternalType('array', $schema);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($schema));
	}

	public function testGetState() {
		$form = new Form(new Controller(), 'TestForm', new FieldList(), new FieldList());
		$formSchema = new FormSchema();
		$expected = [
			'id' => 'TestForm',
			'fields' => [],
			'messages' => []
		];

		$state = $formSchema->getState($form);
		$this->assertInternalType('array', $state);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($state));
	}
}
