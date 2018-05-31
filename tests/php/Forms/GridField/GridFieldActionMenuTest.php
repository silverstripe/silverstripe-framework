<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\Tests\GridField\GridFieldConfigTest\MyActionMenuItemComponent;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class GridFieldActionMenuTest extends SapphireTest
{

    /**
     * @var ArrayList
     */
    protected $list;

    /**
     * @var GridField
     */
    protected $gridField;

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var string
     */
    protected static $fixture_file = 'GridFieldActionTest.yml';

    /**
     * @var array
     */
    protected static $extra_dataobjects = array(
        Team::class,
        Cheerleader::class,
        Player::class,
        Permissions::class,
    );

    protected function setUp()
    {
        parent::setUp();
        $this->list = new DataList(Team::class);
        $config = GridFieldConfig::create()
            ->addComponent(new GridFieldEditButton())
            ->addComponent(new GridFieldDeleteAction())
            ->addComponent(new GridField_ActionMenu());
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'mockform', new FieldList(array($this->gridField)), new FieldList());
    }

    public function testShowActionMenu()
    {
        if (Security::getCurrentUser()) {
            Security::setCurrentUser(null);
        }

        $content = new CSSContentParser($this->gridField->FieldHolder());
        // Check that there are content
        $this->assertEquals(4, count($content->getBySelector('.ss-gridfield-item')));
        // Make sure that there are edit links, even though the user doesn't have "edit" permissions
        // (they can still view the records)
        $this->assertEquals(
            3,
            count($content->getBySelector('.gridfield-actionmenu__container')),
            'Edit links should show when not logged in.'
        );
    }

    public function testHiddenActionMenuItems()
    {
        $config = GridFieldConfig::create()
            ->addComponent(new MyActionMenuItemComponent(true))
            ->addComponent(new GridFieldDeleteAction())
            ->addComponent($menu = new GridField_ActionMenu());
        $this->gridField->setConfig($config);

        $html = $menu->getColumnContent($this->gridField, new Team(), 'test');
        $content = new CSSContentParser($html);
        /* @var \SimpleXMLElement $node */
        $node = $content->getBySelector('.gridfield-actionmenu__container');
        $this->assertNotNull($node);
        $this->assertCount(1, $node);
        $schema = (string) $node[0]->attributes()['data-schema'];
        $json = json_decode($schema, true);
        $this->assertCount(2, $json);

        // Now set the component to not display
        $config = GridFieldConfig::create()
            ->addComponent(new MyActionMenuItemComponent(false))
            ->addComponent(new GridFieldDeleteAction())
            ->addComponent($menu = new GridField_ActionMenu());
        $this->gridField->setConfig($config);

        $html = $menu->getColumnContent($this->gridField, new Team(), 'test');
        $content = new CSSContentParser($html);
        /* @var \SimpleXMLElement $node */
        $node = $content->getBySelector('.gridfield-actionmenu__container');
        $this->assertNotNull($node);
        $this->assertCount(1, $node);
        $schema = (string) $node[0]->attributes()['data-schema'];
        $json = json_decode($schema, true);
        $this->assertCount(1, $json);
    }

    public function testShowEditLinksWithAdminPermission()
    {
        $this->logInWithPermission('ADMIN');
        $content = new CSSContentParser($this->gridField->FieldHolder());
        $editLinks = $content->getBySelector('.gridfield-actionmenu__container');
        $this->assertEquals(3, count($editLinks), 'Edit links should show when logged in.');
    }
}
