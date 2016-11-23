<?php

namespace SilverStripe\Forms\Tests\GridField\GridField_URLHandlerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\ORM\ArrayList;

/**
 * @skipUpgrade
 */
class TestController extends Controller implements TestOnly
{
	public function Link($action = null) {
		return Controller::join_links('GridField_URLHandlerTest_Controller', $action, '/');
	}

	private static $allowed_actions = array('Form');

	public function Form()
	{
		$gridConfig = GridFieldConfig::create();
		$gridConfig->addComponent(new TestComponent());

		$gridData = new ArrayList();
		$gridField = new GridField('Grid', 'My grid', $gridData, $gridConfig);

		/** @skipUpgrade */
		return new Form($this, 'Form', new FieldList(
			$gridField
		), new FieldList());
	}
}
