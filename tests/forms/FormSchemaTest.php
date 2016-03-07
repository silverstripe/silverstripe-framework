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
			'fields' => [
				[
					'id' => 'Form_TestForm_SecurityID',
					'value' => $form->getSecurityToken()->getValue(),
					'messages' => [],
					'valid' => true,
					'data' => []
				]
			],
			'messages' => []
		];

		$state = $formSchema->getState($form);
		$this->assertInternalType('array', $state);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($state));
	}

	public function testGetStateWithFormMessages() {
		$fields = new FieldList();
		$actions = new FieldList();
		$form = new Form(new Controller(), 'TestForm', $fields, $actions);
		$form->sessionMessage('All saved', 'good');
		$formSchema = new FormSchema();
		$expected = [
			'id' => 'TestForm',
			'fields' => [
				[
					'id' => 'Form_TestForm_SecurityID',
					'value' => $form->getSecurityToken()->getValue(),
					'messages' => [],
					'valid' => true,
					'data' => []
				]
			],
			'messages' => [
				[
					'value' => 'All saved',
					'type' => 'good'
				]
			]
		];

		$state = $formSchema->getState($form);
		$this->assertInternalType('array', $state);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($state));
	}

	public function testGetStateWithFieldValidationErrors() {
		$fields = new FieldList(new TextField('Title'));
		$actions = new FieldList();
		$validator = new RequiredFields('Title');
		$form = new Form(new Controller(), 'TestForm', $fields, $actions, $validator);
		$form->loadDataFrom([
			'Title' => 'My Title'
		]);
		$validator->validationError('Title', 'Title is invalid', 'error');
		$formSchema = new FormSchema();
		$expected = [
			'id' => 'TestForm',
			'fields' => [
				[
					'id' => 'Form_TestForm_Title',
					'value' => 'My Title',
					'messages' => [
						['value' => 'Title is invalid', 'type' => 'error']
					],
					'valid' => false,
					'data' => []
				],
				[
					'id' => 'Form_TestForm_SecurityID',
					'value' => $form->getSecurityToken()->getValue(),
					'messages' => [],
					'valid' => true,
					'data' => []
				]
			],
			'messages' => []
		];

		$state = $formSchema->getState($form);
		$this->assertInternalType('array', $state);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($state));
	}
}
