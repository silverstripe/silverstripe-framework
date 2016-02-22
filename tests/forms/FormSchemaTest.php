<?php

use SilverStripe\Forms\Schema\FormSchema;

class FormSchemaTest extends SapphireTest {

	public function testGetSchema() {
		$form = new Form(new Controller(), 'TestForm', new FieldList(), new FieldList());
		$formSchema = new FormSchema();
		$expectedJSON = json_encode([
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
		]);

		$schema = $formSchema->getSchema($form);

		$this->assertJsonStringEqualsJsonString($expectedJSON, $schema);
	}

	public function testGetState() {
		$form = new Form(new Controller(), 'TestForm', new FieldList(), new FieldList());
		$formSchema = new FormSchema();
		$expectedJSON = json_encode(['state' => []]);

		$state = $formSchema->getState($form);

		$this->assertJsonStringEqualsJsonString($expectedJSON, $state);
	}
}
