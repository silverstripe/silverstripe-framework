<?php

use SilverStripe\Forms\Schema\FormSchema;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\PopoverField;

class FormSchemaTest extends SapphireTest {

	public function testGetSchema() {
		$form = new Form(new Controller(), 'TestForm', new FieldList(), new FieldList());
		$formSchema = new FormSchema();
		$expected = [
			'name' => 'TestForm',
			'id' => 'Form_TestForm',
			'action' => 'Controller/TestForm',
			'method' => 'POST',
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
					'id' => 'Form_TestForm_SecurityID',
					'name' => 'SecurityID',
					'type' => "Hidden",
					'component' => null,
					'holderId' => 'Form_TestForm_SecurityID_Holder',
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
			'id' => 'Form_TestForm',
			'fields' => [
				[
					'id' => 'Form_TestForm_SecurityID',
					'value' => $form->getSecurityToken()->getValue(),
					'message' => null,
					'data' => []
				]
			],
			'valid' => null,
			'messages' => [],
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
			'id' => 'Form_TestForm',
			'fields' => [
				[
					'id' => 'Form_TestForm_SecurityID',
					'value' => $form->getSecurityToken()->getValue(),
					'data' => [],
					'message' => null,
				]
			],
			'messages' => [[
				'value' => ['html' => 'All saved'],
				'type' => 'good'
			]],
			'valid' => null,
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
			'Title' => null,
		]);
		$this->assertFalse($form->validate());
		$formSchema = new FormSchema();
		$expected = [
			'id' => 'Form_TestForm',
			'fields' => [
				[
					'id' => 'Form_TestForm_Title',
					'value' => null,
					'message' =>  [
						'value' => ['html' => '&quot;Title&quot; is required'],
						'type' => 'required'
					],
					'data' => []
				],
				[
					'id' => 'Form_TestForm_SecurityID',
					'value' => $form->getSecurityToken()->getValue(),
					'message' => null,
					'data' => []
				]
			],
			'valid' => false,
			'messages' => []
		];

		$state = $formSchema->getState($form);
		$this->assertInternalType('array', $state);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($state));
	}

	public function testGetNestedSchema() {
		$form = new Form(
			new Controller(),
			'TestForm',
			new FieldList(new TextField("Name")),
			new FieldList(
				(new FormAction("save", "Save"))
					->setIcon('save'),
				(new FormAction("cancel", "Cancel"))
					->setUseButtonTag(true),
				new PopoverField("More options", [
					new FormAction("publish", "Publish record"),
					new FormAction("archive", "Archive"),
				])
			)
		);
		$formSchema = new FormSchema();
		/** @skipUpgrade */
		$expected = [
			'name' => 'TestForm',
			'id' => 'Form_TestForm',
			'action' => 'Controller/TestForm',
			'method' => 'POST',
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
					'id' => 'Form_TestForm_Name',
					'name' => 'Name',
					'type' => 'Text',
				 	'component' => null,
				 	'holderId' => 'Form_TestForm_Name_Holder',
					'title' => 'Name',
					'source' => null,
					'extraClass' => 'text',
					'description' => null,
					'rightTitle' => null,
					'leftTitle' => null,
					'readOnly' => false,
					'disabled' => false,
					'customValidationMessage' => '',
					'attributes' => [],
					'data' => [],
				],
				[
					'id' => 'Form_TestForm_SecurityID',
					'name' => 'SecurityID',
					'type' => "Hidden",
					'component' => null,
					'holderId' => 'Form_TestForm_SecurityID_Holder',
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
			'actions' => [
				[
					'id' => 'Form_TestForm_action_save',
					'title' => 'Save',
					'name' => 'action_save',
					'type' => null,
					'component' => 'FormAction',
					'holderId' => 'Form_TestForm_action_save_Holder',
					'source' => null,
					'extraClass' => 'action',
					'description' => null,
					'rightTitle' => null,
					'leftTitle' => null,
					'readOnly' => false,
					'disabled' => false,
					'customValidationMessage' => '',
					'attributes' => [
						'type' => 'submit',
					],
					'data' => [
						'icon' => 'save',
					],
				],
				[
					'id' => 'Form_TestForm_action_cancel',
					'title' => 'Cancel',
					'name' => 'action_cancel',
					'type' => null,
					'component' => 'FormAction',
					'holderId' => 'Form_TestForm_action_cancel_Holder',
					'source' => null,
					'extraClass' => 'action',
					'description' => null,
					'rightTitle' => null,
					'leftTitle' => null,
					'readOnly' => false,
					'disabled' => false,
					'customValidationMessage' => '',
					'attributes' => [
						'type' => 'button'
					],
					'data' => [
						'icon' => null
					],
				],
				[
					'id' => 'Form_TestForm_Moreoptions',
					'title' => 'More options',
					'name' => 'Moreoptions',
					'type' => 'Structural',
					'component' => 'PopoverField',
					'holderId' => 'Form_TestForm_Moreoptions_Holder',
					'source' => null,
					'extraClass' => 'field CompositeField popover',
					'description' => null,
					'rightTitle' => null,
					'leftTitle' => null,
					'readOnly' => null,
					'disabled' => false,
					'customValidationMessage' => '',
					'attributes' => [],
					'data' => [
						'popoverTitle' => null,
						'placement' => 'bottom',
						'tag' => 'div',
						'legend' => null,
					],
					'children' => [
						[
							'id' => 'Form_TestForm_action_publish',
							'title' => 'Publish record',
							'name' => 'action_publish',
							'type' => null,
							'component' => 'FormAction',
							'holderId' => 'Form_TestForm_action_publish_Holder',
							'source' => null,
							'extraClass' => 'action',
							'description' => null,
							'rightTitle' => null,
							'leftTitle' => null,
							'readOnly' => false,
							'disabled' => false,
							'customValidationMessage' => '',
							'attributes' => [
								'type' => 'submit',
							],
							'data' => [
								'icon' => null,
							],
						],
						[
							'id' => 'Form_TestForm_action_archive',
							'title' => 'Archive',
							'name' => 'action_archive',
							'type' => null,
							'component' => 'FormAction',
							'holderId' => 'Form_TestForm_action_archive_Holder',
							'source' => null,
							'extraClass' => 'action',
							'description' => null,
							'rightTitle' => null,
							'leftTitle' => null,
							'readOnly' => false,
							'disabled' => false,
							'customValidationMessage' => '',
							'attributes' => [
								'type' => 'submit',
							],
							'data' => [
								'icon' => null,
							],
						],
					]
				]
			]
		];

		$schema = $formSchema->getSchema($form);

		$this->assertInternalType('array', $schema);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($schema));
	}

	/**
	 * Test that schema is merged correctly
	 */
	public function testMergeSchema() {
		$publishAction = FormAction::create('publish', 'Publish');
		$publishAction->setIcon('save');
		$publishAction->setSchemaData(['data' => ['buttonStyle' => 'primary']]);
		$schema = $publishAction->getSchemaData();
		$this->assertEquals(
			[
				'icon' => 'save',
				'buttonStyle' => 'primary',
			],
			$schema['data']
		);

	}
}
