<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class WidgetAreaEditorTest extends SapphireTest {
	/**
	 * This is the widget you want to use for your unit tests.
	 */
	protected $widgetToTest = 'WidgetAreaEditorTest_TestWidget';

	protected $extraDataObjects = array(
		'WidgetAreaEditorTest_FakePage',
		'WidgetAreaEditorTest_TestWidget',
	);
	
	function testFillingOneArea() {
		$oldRequest = $_REQUEST;
		
		$_REQUEST = array(
			'Widget' => array(
				'BottomBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidget',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		
		$editorSide = new WidgetAreaEditor('SideBar');
		$editorBott = new WidgetAreaEditor('BottomBar');
		$page = new WidgetAreaEditorTest_FakePage();

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();

		$this->assertEquals($page->BottomBar()->Widgets()->Count(), 1);
		$this->assertEquals($page->SideBar()->Widgets()->Count(), 0);
		
		$_REQUEST = $oldRequest;
	}

	function testFillingTwoAreas() {
		$oldRequest = $_REQUEST;
		
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetSide',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		
		$editorSide = new WidgetAreaEditor('SideBar');
		$editorBott = new WidgetAreaEditor('BottomBar');
		$page = new WidgetAreaEditorTest_FakePage();

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		
		// Make sure they both got saved
		$this->assertEquals($page->BottomBar()->Widgets()->Count(), 1);
		$this->assertEquals($page->SideBar()->Widgets()->Count(), 1);
		
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		$this->assertEquals($sideWidgets[0]->Title(), 'MyTestWidgetSide');
		$this->assertEquals($bottWidgets[0]->Title(), 'MyTestWidgetBottom');
		
		$_REQUEST = $oldRequest;
	}
		
	function testDeletingOneWidgetFromOneArea() {
		$oldRequest = $_REQUEST;
		
		// First get some widgets in there
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetSide',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		
		$editorSide = new WidgetAreaEditor('SideBar');
		$editorBott = new WidgetAreaEditor('BottomBar');
		$page = new WidgetAreaEditorTest_FakePage();

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		// Save again (after removing the SideBar's widget)
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
				),
				'BottomBar' => array(
					$bottWidgets[0]->ID => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);

		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		$this->assertEquals($page->BottomBar()->Widgets()->Count(), 1);
		$this->assertEquals($bottWidgets[0]->Title(), 'MyTestWidgetBottom');
		$this->assertEquals($page->SideBar()->Widgets()->Count(), 0);
		
		$_REQUEST = $oldRequest;
	}

	function testDeletingAWidgetFromEachArea() {
		$oldRequest = $_REQUEST;
		
		// First get some widgets in there
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetSide',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		
		$editorSide = new WidgetAreaEditor('SideBar');
		$editorBott = new WidgetAreaEditor('BottomBar');
		$page = new WidgetAreaEditorTest_FakePage();

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		// Save again (after removing the SideBar's widget)
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
				),
				'BottomBar' => array(
				)
			)
		);

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		$this->assertEquals($page->BottomBar()->Widgets()->Count(), 0);
		$this->assertEquals($page->SideBar()->Widgets()->Count(), 0);
		
		$_REQUEST = $oldRequest;
	}
	
	function testEditingOneWidget() {
		$oldRequest = $_REQUEST;
		
		// First get some widgets in there
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetSide',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		
		$editorSide = new WidgetAreaEditor('SideBar');
		$editorBott = new WidgetAreaEditor('BottomBar');
		$page = new WidgetAreaEditorTest_FakePage();

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		// Save again (after removing the SideBar's widget)
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					$sideWidgets[0]->ID => array(
						'Title' => 'MyTestWidgetSide-edited',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					$bottWidgets[0]->ID => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);

		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		$this->assertEquals($page->BottomBar()->Widgets()->Count(), 1);
		$this->assertEquals($page->SideBar()->Widgets()->Count(), 1);
		$this->assertEquals($bottWidgets[0]->Title(), 'MyTestWidgetBottom');
		$this->assertEquals($sideWidgets[0]->Title(), 'MyTestWidgetSide-edited');
		
		
		$_REQUEST = $oldRequest;
	}

	function testEditingAWidgetFromEachArea() {
		$oldRequest = $_REQUEST;
		
		// First get some widgets in there
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetSide',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		
		$editorSide = new WidgetAreaEditor('SideBar');
		$editorBott = new WidgetAreaEditor('BottomBar');
		$page = new WidgetAreaEditorTest_FakePage();

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		// Save again (after removing the SideBar's widget)
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					$sideWidgets[0]->ID => array(
						'Title' => 'MyTestWidgetSide-edited',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					$bottWidgets[0]->ID => array(
						'Title' => 'MyTestWidgetBottom-edited',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);

		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		$this->assertEquals($page->BottomBar()->Widgets()->Count(), 1);
		$this->assertEquals($page->SideBar()->Widgets()->Count(), 1);
		$this->assertEquals($bottWidgets[0]->Title(), 'MyTestWidgetBottom-edited');
		$this->assertEquals($sideWidgets[0]->Title(), 'MyTestWidgetSide-edited');
		
		
		$_REQUEST = $oldRequest;
	}
	
	function testEditAWidgetFromOneAreaAndDeleteAWidgetFromAnotherArea() {
		$oldRequest = $_REQUEST;
		
		// First get some widgets in there
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetSide',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
					'new-1' => array(
						'Title' => 'MyTestWidgetBottom',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				)
			)
		);
		
		$editorSide = new WidgetAreaEditor('SideBar');
		$editorBott = new WidgetAreaEditor('BottomBar');
		$page = new WidgetAreaEditorTest_FakePage();

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);
		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		// Save again (after removing the SideBar's widget)
		$_REQUEST = array(
			'Widget' => array(
				'SideBar' => array(
					$sideWidgets[0]->ID => array(
						'Title' => 'MyTestWidgetSide-edited',
						'Type' => $this->widgetToTest,
						'Sort' => 0
					)
				),
				'BottomBar' => array(
				)
			)
		);
		

		$editorSide->saveInto($page);
		$editorBott->saveInto($page);

		$page->write();
		$page->flushCache();
		$page->BottomBar()->flushCache();
		$page->SideBar()->flushCache();
		$sideWidgets = $page->SideBar()->Widgets()->toArray();
		$bottWidgets = $page->BottomBar()->Widgets()->toArray();
		
		$this->assertEquals($page->BottomBar()->Widgets()->Count(), 0);
		$this->assertEquals($page->SideBar()->Widgets()->Count(), 1);
		$this->assertEquals($sideWidgets[0]->Title(), 'MyTestWidgetSide-edited');
		
		
		$_REQUEST = $oldRequest;
	}
}

class WidgetAreaEditorTest_FakePage extends Page implements TestOnly {
	public static $has_one = array(
		"SideBar" => "WidgetArea",
		"BottomBar" => "WidgetArea",
	);
}

class WidgetAreaEditorTest_TestWidget extends Widget implements TestOnly {
	static $cmsTitle = "Test widget";
	static $title = "Test widget";
	static $description = "Test widget";
	static $db = array(
		'Title' => 'Varchar'
	);
	public function getCMSFields() {
		$fields = new FieldSet();
		$fields->push(new TextField('Title'));
		return $fields;
	}
	function Title() {
		return $this->Title ? $this->Title : self::$title;
	}
}