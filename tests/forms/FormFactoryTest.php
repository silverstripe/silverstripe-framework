<?php

use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DefaultFormFactory;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
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
		$factory = new FormFactoryTest_EditFactory();
		return $factory->getForm($this, 'Form', ['Record' => $record]);
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
 * Provides versionable extensions to a controller / scaffolder
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
	 * @param Controller $controller
	 * @param string $name
	 * @param array $context
	 */
	public function updateFormActions(FieldList &$actions, Controller $controller, $name, $context = []) {
		// Add publish button if record is versioned
		if (empty($context['Record'])) {
			return;
		}
		$record = $context['Record'];
		if ($record->hasExtension(Versioned::class)) {
			$actions->push(new FormAction('publish', 'Publish'));
		}
	}

	/**
	 * Adds extra fields to this form
	 *
	 * @param FieldList $fields
	 * @param Controller $controller
	 * @param string $name
	 * @param array $context
	 */
	public function updateFormFields(FieldList &$fields, Controller $controller, $name, $context = []) {
		// Add preview link
		if (empty($context['Record'])) {
			return;
		}
		$record = $context['Record'];
		if ($record->hasExtension(Versioned::class)) {
			$link = $controller->Link('preview');
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

	private static $extensions = [
		FormFactoryTest_ControllerExtension::class
	];

	protected function getFormFields(Controller $controller, $name, $context = [])
	{
		$fields = new FieldList(
			new HiddenField('ID'),
			new TextField('Title')
		);
		$this->invokeWithExtensions('updateFormFields', $fields, $controller, $name, $context);
		return $fields;
	}

	protected function getFormActions(Controller $controller, $name, $context = [])
	{
		$actions = new FieldList(
			new FormAction('save', 'Save')
		);
		$this->invokeWithExtensions('updateFormActions', $actions, $controller, $name, $context);
		return $actions;
	}
}
