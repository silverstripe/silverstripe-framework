<?php

use SilverStripe\ORM\ArrayList;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\View\SSViewer;






/**
 * Test the API for creating GridField_URLHandler compeonnts
 */
class GridField_URLHandlerTest extends FunctionalTest {
	public function testFormSubmission() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/showform");
		$formResult = $this->submitForm('Form_Form', 'action_doAction', array('Test' => 'foo bar') );
		$this->assertEquals("Submitted foo bar to component", $formResult->getBody());
	}

	public function testNestedRequestHandlerFormSubmission() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/item/3/showform");
		$formResult = $this->submitForm('Form_Form', 'action_doAction', array('Test' => 'foo bar') );
		$this->assertEquals("Submitted foo bar to item #3", $formResult->getBody());
	}

	public function testURL() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/testpage");
		$this->assertEquals("Test page for component", $result->getBody());
	}

	public function testNestedRequestHandlerURL() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/item/5/testpage");
		$this->assertEquals("Test page for item #5", $result->getBody());
	}


}

class GridField_URLHandlerTest_Controller extends Controller implements TestOnly {

	private static $allowed_actions = array('Form');

	public function Form() {
		$gridConfig = GridFieldConfig::create();
		$gridConfig->addComponent(new GridField_URLHandlerTest_Component());

		$gridData = new ArrayList();
		$gridField = new GridField('Grid', 'My grid', $gridData, $gridConfig);

		/** @skipUpgrade */
		return new Form($this, 'Form', new FieldList(
			$gridField
		), new FieldList());
	}
}


/**
 * Test URLHandler with a nested request handler
 */
class GridField_URLHandlerTest_Component extends RequestHandler implements GridField_URLHandler {

	private static $allowed_actions = array('Form', 'showform', 'testpage', 'handleItem');

	protected $gridField;

	public function getURLHandlers($gridField) {
		/** @skipUpgrade */
		return array(
			'showform' => 'showform',
			'testpage' => 'testpage',
			'Form' => 'Form',
			'item/$ID' => 'handleItem',
		);
	}

	public function handleItem($gridField, $request) {
		$id = $request->param("ID");
		return new GridField_URLHandlerTest_Component_ItemRequest(
				$gridField, $id,
				Controller::join_links($gridField->Link(), 'item/' . $id));
	}

	public function Link() {
		return $this->gridField->Link();
	}

	public function showform($gridField, $request) {
		return "<head>" .  SSViewer::get_base_tag("") . "</head>" . $this->Form($gridField, $request)->forTemplate();
	}

	public function Form($gridField, $request) {
		$this->gridField = $gridField;
		/** @skipUpgrade */
		return new Form($this, 'Form', new FieldList(
			new TextField("Test")
		), new FieldList(
			new FormAction('doAction', 'Go')
		));
	}

	public function doAction($data, $form) {
		return "Submitted " . $data['Test'] . " to component";
	}

	public function testpage($gridField, $request) {
		return "Test page for component";
	}
}

class GridField_URLHandlerTest_Component_ItemRequest extends RequestHandler {

	private static $allowed_actions = array('Form', 'showform', 'testpage');

	protected $gridField;

	protected $link;

	protected $id;

	public function __construct($gridField, $id, $link) {
		$this->gridField = $gridField;
		$this->id = $id;
		$this->link = $link;
		parent::__construct();
	}

	public function Link() {
		return $this->link;
	}

	public function showform() {
		return "<head>" .  SSViewer::get_base_tag("") . "</head>" . $this->Form()->forTemplate();
	}

	public function Form() {
		/** @skipUpgrade */
		return new Form($this, 'Form', new FieldList(
			new TextField("Test")
		), new FieldList(
			new FormAction('doAction', 'Go')
		));
	}

	public function doAction($data, $form) {
		return "Submitted " . $data['Test'] . " to item #" . $this->id;
	}

	public function testpage() {
		return "Test page for item #" . $this->id;
	}
}
