<?php

class GridFieldEditButtonTest extends SapphireTest {
	
	/** @var ArrayList */
	protected $list;
	
	/** @var GridField */
	protected $gridField;
	
	/** @var Form */
	protected $form;
	
	/** @var string */
	public static $fixture_file = 'GridFieldActionTest.yml';

	/** @var array */
	protected $extraDataObjects = array('GridFieldAction_Delete_Team', 'GridFieldAction_Edit_Team');
	
	public function setUp() {
		parent::setUp();
		$this->list = new DataList('GridFieldAction_Edit_Team');
		$config = GridFieldConfig::create()->addComponent(new GridFieldEditButton());
		$this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
		$this->form = new Form(new Controller(), 'mockform', new FieldList(array($this->gridField)), new FieldList());
	}
	
	public function testDontShowEditLinks() {
		if(Member::currentUser()) { Member::currentUser()->logOut(); }
		
		$content = new CSSContentParser($this->gridField->FieldHolder());
		// Check that there are content
		$this->assertEquals(3, count($content->getBySelector('.ss-gridfield-item')));
		// Make sure that there are no edit links
		$this->assertEquals(0, count($content->getBySelector('.edit-link')), 'Edit links should not show when not logged in.');
	}
	
	public function testShowEditLinksWithAdminPermission() {
		$this->logInWithPermission('ADMIN');
		$content = new CSSContentParser($this->gridField->FieldHolder());
		$editLinks = $content->getBySelector('.edit-link');
		$this->assertEquals(2, count($editLinks), 'Edit links should show when logged in.');
	}
}

class GridFieldAction_Edit_Team extends DataObject implements TestOnly {
	static $db = array(
		'Name' => 'Varchar',
		'City' => 'Varchar'
	);
	
	public function canView($member = null) {
		return true;
	}
}
