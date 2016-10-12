<?php

use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DefaultFormFactory;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormFactory;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

class FormFactoryTest extends SapphireTest
{
	protected $extraDataObjects = [
		FormFactoryTest_TestObject::class,
	];

	protected static $fixture_file = 'FormFactoryTest.yml';

	/**
	 * Test versioned form
	 */
	public function testVersionedForm() {
		$controller = new FormFactoryTest_TestController();
		$form = $controller->Form();

		// Check formfields
		$this->assertInstanceOf(TextField::class, $form->Fields()->fieldByName('Title'));
		$this->assertInstanceOf(HiddenField::class, $form->Fields()->fieldByName('ID'));
		$this->assertInstanceOf(HiddenField::class, $form->Fields()->fieldByName('SecurityID'));


		// Check preview link
		/** @var LiteralField $previewLink */
		$previewLink = $form->Fields()->fieldByName('PreviewLink');
		$this->assertInstanceOf(LiteralField::class, $previewLink);
		$this->assertEquals(
			'<a href="FormFactoryTest_TestController/preview/" rel="external" target="_blank">Preview</a>',
			$previewLink->getContent()
		);

		// Check actions
		$this->assertInstanceOf(FormAction::class, $form->Actions()->fieldByName('action_save'));
		$this->assertInstanceOf(FormAction::class, $form->Actions()->fieldByName('action_publish'));
		$this->assertTrue($controller->hasAction('publish'));
	}

	/**
	 * Removing versioning from an object should result in a simpler form
	 */
	public function testBasicForm() {
		FormFactoryTest_TestObject::remove_extension(Versioned::class);
		$controller = new FormFactoryTest_TestController();
		$form = $controller->Form();

		// Check formfields
		$this->assertInstanceOf(TextField::class, $form->Fields()->fieldByName('Title'));
		$this->assertNull($form->Fields()->fieldByName('PreviewLink'));

		// Check actions
		$this->assertInstanceOf(FormAction::class, $form->Actions()->fieldByName('action_save'));
		$this->assertNull($form->Actions()->fieldByName('action_publish'));
	}
}

/**
 * @mixin Versioned
 */
class FormFactoryTest_TestObject extends DataObject {
	private static $db = [
		'Title' => 'Varchar',
	];

	private static $extensions = [
		Versioned::class,
	];
}

/**
 * Edit controller for this form
 */
class FormFactoryTest_TestController extends Controller {
	private static $extensions = [
		FormFactoryTest_ControllerExtension::class,
	];

	public function Link($action = null) {
		return Controller::join_links('FormFactoryTest_TestController', $action, '/');
	}

	public function Form() {
		// Simple example; Just get the first draft record
		$record = $this->getRecord();
		$factory = new FormFactoryTest_EditFactory($this, $record);
		return $factory->getForm('Form');
	}

	public function save($data, Form $form) {
		// Noop
	}

	/**
	 * @return DataObject
	 */
	protected function getRecord()
	{
		return Versioned::get_by_stage(FormFactoryTest_TestObject::class, Versioned::DRAFT)->first();
	}
}

/**
 * Provides versionable extensions to a controller
 */
class FormFactoryTest_ControllerExtension extends Extension {

	/**
	 * Handlers for extra actions added by this extension
	 *
	 * @var array
	 */
	private static $allowed_actions = [
		'publish',
		'preview',
	];

	/**
	 * Adds additional form actions
	 *
	 * @param FieldList $actions
	 * @param FormFactory $factory
	 */
	public function updateFormActions(FieldList &$actions, FormFactory &$factory) {
		$record = $factory->getRecord();
		if ($record->hasExtension(Versioned::class)) {
			$actions->push(new FormAction('publish', 'Publish'));
		}
	}

	/**
	 * Adds extra fields to this form
	 *
	 * @param FieldList $fields
	 * @param FormFactory $factory
	 */
	public function updateFormFields(FieldList &$fields, FormFactory &$factory) {
		// Add preview link
		if ($factory->getRecord()->hasExtension(Versioned::class)) {
			$link = $factory->getController()->Link('preview');
			$fields->unshift(new LiteralField(
				"PreviewLink",
				sprintf('<a href="%s" rel="external" target="_blank">Preview</a>', Convert::raw2att($link))
			));
		}
	}

	public function publish($data, $form) {
		// noop
	}

	public function preview() {
		// noop
	}
}

/**
 * Test factory
 */
class FormFactoryTest_EditFactory extends DefaultFormFactory  {

	protected function getFormFields()
	{
		$fields = new FieldList(
			new HiddenField('ID'),
			new TextField('Title')
		);
		$this->extendAll('updateFormFields', $fields);
		return $fields;
	}

	public function getFormActions()
	{
		$actions = new FieldList(
			new FormAction('save', 'Save')
		);
		$this->extendAll('updateFormActions', $actions);
		return $actions;
	}
}
