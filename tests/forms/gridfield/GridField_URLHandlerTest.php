<?php

/**
 * Test the API for creating GridField_URLHandler compeonnts
 */
class GridField_URLHandlerTest extends FunctionalTest {
	function testFormSubmission() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/showform");
		$formResult = $this->submitForm('Form_Form', 'action_doAction', array('Test' => 'foo bar') );
		$this->assertEquals("Submitted foo bar to component", $formResult->getBody());
	}

	function testNestedRequestHandlerFormSubmission() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/item/3/showform");
		$formResult = $this->submitForm('Form_Form', 'action_doAction', array('Test' => 'foo bar') );
		$this->assertEquals("Submitted foo bar to item #3", $formResult->getBody());
	}

	function testURL() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/testpage");
		$this->assertEquals("Test page for component", $result->getBody());
	}

	function testNestedRequestHandlerURL() {
		$result = $this->get("GridField_URLHandlerTest_Controller/Form/field/Grid/item/5/testpage");
		$this->assertEquals("Test page for item #5", $result->getBody());
	}


}

class GridField_URLHandlerTest_Controller extends Controller implements TestOnly {
	function Link() {
		return get_class($this) ."/";
	}
	function Form() {
		$gridConfig = GridFieldConfig::create();
		$gridConfig->addComponent(new GridField_URLHandlerTest_Component());
		
		$gridData = new ArrayList();
		$gridField = new GridField('Grid', 'My grid', $gridData, $gridConfig);
		
		return new Form($this, 'Form', new FieldList(
			$gridField
		), new FieldList());
	}
}


/**
 * Test URLHandler with a nested request handler
 */
class GridField_URLHandlerTest_Component extends RequestHandler implements GridField_URLHandler {
	protected $gridField;
	
	function getURLHandlers($gridField) {
		return array(
			'showform' => 'showform',
			'testpage' => 'testpage',
			'Form' => 'Form',
			'item/$ID' => 'handleItem',
		);
	}
	
	function handleItem($gridField, $request) {
		$id = $request->param("ID");
		return new GridField_URLHandlerTest_Component_ItemRequest(
				$gridField, $id,
				Controller::join_links($gridField->Link(), 'item/' . $id));
	}
	
	function Link() {
		return $this->gridField->Link();
	}
	
	function showform($gridField, $request) {
		return "<head>" .  SSViewer::get_base_tag("") . "</head>" . $this->Form($gridField, $request)->forTemplate();
	}
	
	function Form($gridField, $request) {
		$this->gridField = $gridField;
		return new Form($this, 'Form', new FieldList(
			new TextField("Test")
		), new FieldList(
			new FormAction('doAction', 'Go')
		));
	}

	function doAction($data, $form) {
		return "Submitted " . $data['Test'] . " to component";
	}

	function testpage($gridField, $request) {
		return "Test page for component";
	}
}

class GridField_URLHandlerTest_Component_ItemRequest extends RequestHandler {
	protected $gridField;
	protected $link;
	protected $id;
	
	function __construct($gridField, $id, $link) {
		$this->gridField = $gridField;
		$this->id = $id;
		$this->link = $link;
		parent::__construct();
	}
	
	function Link() {
		return $this->link;
	}
	
	function showform() {
		return "<head>" .  SSViewer::get_base_tag("") . "</head>" . $this->Form()->forTemplate();
	}

	function Form() {
		return new Form($this, 'Form', new FieldList(
			new TextField("Test")
		), new FieldList(
			new FormAction('doAction', 'Go')
		));
	}
	
	function doAction($data, $form) {
		return "Submitted " . $data['Test'] . " to item #" . $this->id;
	}

	function testpage() {
		return "Test page for item #" . $this->id;
	}
}
