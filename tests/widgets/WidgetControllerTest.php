<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class WidgetControllerTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/widgets/WidgetControllerTest.yml';

	protected $extraDataObjects = array(
		'WidgetControllerTestPage',
		'WidgetControllerTest_Widget',
	);
	
	function testWidgetFormRendering() {
		$page = $this->objFromFixture('WidgetControllerTestPage', 'page1');
		$page->publish('Stage', 'Live');
		
		$widget = $this->objFromFixture('WidgetControllerTest_Widget', 'widget1');
		
		$response = $this->get($page->URLSegment);
		
		$formAction = sprintf('%s/widget/%d/Form', $page->URLSegment, $widget->ID);
		$this->assertContains(
			$formAction, 
			$response->getBody(),
			"Widget forms are rendered through WidgetArea templates"
		);
	}
	
	function testWidgetFormSubmission() {
		$page = $this->objFromFixture('WidgetControllerTestPage', 'page1');
		$page->publish('Stage', 'Live');
		
		$widget = $this->objFromFixture('WidgetControllerTest_Widget', 'widget1');
		
		$this->get($page->URLSegment);
		$response = $this->submitForm('Form_Form', null, array('TestValue'=>'Updated'));
		
		$this->assertContains(
			'TestValue: Updated', 
			$response->getBody(),
			"Form values are submitted to correct widget form"
		);
		$this->assertContains(
			sprintf('Widget ID: %d', $widget->ID), 
			$response->getBody(),
			"Widget form acts on correct widget, as identified in the URL"
		);
	}
}

/**
 * @package sapphire
 * @subpackage tests
 */
class WidgetControllerTest_Widget extends Widget implements TestOnly {
	static $db = array(
		'TestValue' => 'Text'
	);
}

/**
 * @package sapphire
 * @subpackage tests
 */
class WidgetControllerTest_Widget_Controller extends Widget_Controller implements TestOnly {
	function Form() {
		$widgetform = new Form(
			$this, 
			'Form', 
			new FieldSet(
				new TextField('TestValue')
			), 
			new FieldSet(
				new FormAction('doAction')
			)
		);

		return $widgetform;
	}
	
	function doAction($data, $form) {
		return sprintf('TestValue: %s\nWidget ID: %d',
			$data['TestValue'],
			$this->widget->ID
		);
	}
}
?>