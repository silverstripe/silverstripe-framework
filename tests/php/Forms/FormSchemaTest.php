<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\NumericField;
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
					'data' => [],
					'validation' => [],
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
					'data' => [],
					'name' => 'SecurityID',
				]
			],
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
					'name' => 'SecurityID',
				]
			],
			'messages' => [[
				'value' => 'All saved',
				'type' => 'good'
			]],
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
		$this->assertFalse($form->validationResult()->isValid());
		$formSchema = new FormSchema();
		$expected = [
			'id' => 'Form_TestForm',
			'fields' => [
				[
					'id' => 'Form_TestForm_Title',
					'value' => null,
					'message' =>  [
						'value' => '"Title" is required',
						'type' => 'required'
					],
					'data' => [],
					'name' => 'Title',
				],
				[
					'id' => 'Form_TestForm_SecurityID',
					'value' => $form->getSecurityToken()->getValue(),
					'message' => null,
					'data' => [],
					'name' => 'SecurityID',
				]
			],
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
				$pop = new PopoverField("More options", [
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
					'validation' => [],
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
					'data' => [],
					'validation' => [],
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
					'validation' => [],
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
					'validation' => [],
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
                        'buttonTooltip' => null,
					],
					'validation' => [],
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
							'validation' => [],
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
							'validation' => [],
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

	public function testSchemaValidation() {
		$form = new Form(
			new Controller(),
			'TestForm',
			new FieldList(
				TextField::create("Name")
					->setMaxLength(40),
				new DateField("Date"),
				new NumericField("Number"),
				new CurrencyField("Money")
			),
			new FieldList(),
			new RequiredFields('Name')
		);
		$formSchema = new FormSchema();
		$schema = $formSchema->getSchema($form);
		$expected = [
			'name' => 'TestForm',
			'id' => 'Form_TestForm',
			'action' => 'Controller/TestForm',
			'method' => 'POST',
			'attributes' =>
				[
					'id' => 'Form_TestForm',
					'action' => 'Controller/TestForm',
					'method' => 'POST',
					'enctype' => 'application/x-www-form-urlencoded',
					'target' => null,
					'class' => '',
				],
			'data' =>
				[],
			'fields' =>
				[
					[
						'name' => 'Name',
						'id' => 'Form_TestForm_Name',
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
						'validation' =>
							[
								'required' => true,
								'max' => [
									'length' => 40,
								],
							],
						'attributes' =>
							[],
						'data' =>
							[],
					],
					[
						'name' => 'Date',
						'id' => 'Form_TestForm_Date',
						'type' => 'Date',
						'component' => null,
						'holderId' => 'Form_TestForm_Date_Holder',
						'title' => 'Date',
						'source' => null,
						'extraClass' => 'date text',
						'description' => null,
						'rightTitle' => null,
						'leftTitle' => null,
						'readOnly' => false,
						'disabled' => false,
						'customValidationMessage' => '',
						'validation' =>
							[
								'date' => true,
							],
						'attributes' =>
							[],
						'data' =>
							[],
					],
					[
						'name' => 'Number',
						'id' => 'Form_TestForm_Number',
						'type' => 'Decimal',
						'component' => null,
						'holderId' => 'Form_TestForm_Number_Holder',
						'title' => 'Number',
						'source' => null,
						'extraClass' => 'numeric text',
						'description' => null,
						'rightTitle' => null,
						'leftTitle' => null,
						'readOnly' => false,
						'disabled' => false,
						'customValidationMessage' => '',
						'validation' =>
							[
								'numeric' => true,
							],
						'attributes' =>
							[],
						'data' =>
							[],
					],
					[
						'name' => 'Money',
						'id' => 'Form_TestForm_Money',
						'type' => 'Text',
						'component' => null,
						'holderId' => 'Form_TestForm_Money_Holder',
						'title' => 'Money',
						'source' => null,
						'extraClass' => 'currency text',
						'description' => null,
						'rightTitle' => null,
						'leftTitle' => null,
						'readOnly' => false,
						'disabled' => false,
						'customValidationMessage' => '',
						'validation' =>
							[
								'currency' => true,
							],
						'attributes' =>
							[],
						'data' =>
							[],
					],
					[
						'name' => 'SecurityID',
						'id' => 'Form_TestForm_SecurityID',
						'type' => 'Hidden',
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
						'validation' =>
							[],
						'attributes' =>
							[],
						'data' =>
							[],
					],
				],
			'actions' =>
				[],
		];

		$this->assertInternalType('array', $schema);
		$this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($schema));
	}
}
