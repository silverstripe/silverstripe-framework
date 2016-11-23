<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridField;

class GridFieldEditButtonTest extends SapphireTest {

	/** @var ArrayList */
	protected $list;

	/** @var GridField */
	protected $gridField;

	/** @var Form */
	protected $form;

	/** @var string */
	protected static $fixture_file = 'GridFieldActionTest.yml';

	/** @var array */
	protected $extraDataObjects = array(
		Team::class,
		Cheerleader::class,
		Player::class,
		Permissions::class
	);

	public function setUp() {
		parent::setUp();
		$this->list = new DataList(Team::class);
		$config = GridFieldConfig::create()->addComponent(new GridFieldEditButton());
		$this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
		$this->form = new Form(new Controller(), 'mockform', new FieldList(array($this->gridField)), new FieldList());
	}

	public function testShowEditLinks() {
		if(Member::currentUser()) { Member::currentUser()->logOut(); }

		$content = new CSSContentParser($this->gridField->FieldHolder());
		// Check that there are content
		$this->assertEquals(4, count($content->getBySelector('.ss-gridfield-item')));
		// Make sure that there are edit links, even though the user doesn't have "edit" permissions
		// (they can still view the records)
		$this->assertEquals(3, count($content->getBySelector('.edit-link')),
			'Edit links should show when not logged in.');
	}

	public function testShowEditLinksWithAdminPermission() {
		$this->logInWithPermission('ADMIN');
		$content = new CSSContentParser($this->gridField->FieldHolder());
		$editLinks = $content->getBySelector('.edit-link');
		$this->assertEquals(3, count($editLinks), 'Edit links should show when logged in.');
	}
}
