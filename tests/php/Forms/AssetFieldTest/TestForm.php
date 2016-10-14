<?php

namespace SilverStripe\Forms\Tests\AssetFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\AssetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;

class TestForm extends Form implements TestOnly
{

	public function getRecord()
	{
		if (empty($this->record)) {
			$this->record = TestObject::get()
				->filter('Title', 'Object1')
				->first();
		}
		return $this->record;
	}

	/**
	 * @skipUpgrade
	 * @param null $controller
	 * @param string $name
	 */
	public function __construct($controller = null, $name = 'Form')
	{
		if (empty($controller)) {
			$controller = new TestController();
		}

		$fields = new FieldList(
			AssetField::create('File')
				->setFolderName('MyFiles'),
			AssetField::create('Image')
				->setAllowedFileCategories('image/supported')
				->setFolderName('MyImages'),
			AssetField::create('NoRelationField')
				->setFolderName('MyDocuments')
		);
		$actions = new FieldList(
			new FormAction('submit')
		);
		$validator = new RequiredFields();

		parent::__construct($controller, $name, $fields, $actions, $validator);

		$this->loadDataFrom($this->getRecord());
	}

	public function submit($data, Form $form)
	{
		$record = $this->getRecord();
		$form->saveInto($record);
		$record->write();
		return json_encode($record->toMap());
	}
}
