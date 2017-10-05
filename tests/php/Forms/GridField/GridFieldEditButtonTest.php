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
use SilverStripe\Security\Security;

class GridFieldEditButtonTest extends SapphireTest
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
        $config = GridFieldConfig::create()->addComponent(new GridFieldEditButton());
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'mockform', new FieldList(array($this->gridField)), new FieldList());
    }

    public function testShowEditLinks()
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
            count($content->getBySelector('.edit-link')),
            'Edit links should show when not logged in.'
        );
    }

    public function testShowEditLinksWithAdminPermission()
    {
        $this->logInWithPermission('ADMIN');
        $content = new CSSContentParser($this->gridField->FieldHolder());
        $editLinks = $content->getBySelector('.edit-link');
        $this->assertEquals(3, count($editLinks), 'Edit links should show when logged in.');
    }

    public function testDefaultClassesAreSet()
    {
        $button = new GridFieldEditButton;

        $expected = [
            'grid-field__icon-action--hidden-on-hover',
            'font-icon-edit',
            'btn--icon-large'
        ];

        $result = $button->getExtraClass();

        foreach ($expected as $className) {
            $this->assertContains($className, $result);
        }
    }

    public function testAddAndRemoveExtraClass()
    {
        $button = new GridFieldEditButton;

        $button->addExtraClass('foobar');
        $this->assertContains('foobar', $button->getExtraClass());

        $button->removeExtraClass('foobar');
        $this->assertNotContains('foobar', $button->getExtraClass());

        // Check that duplicates are removed
        $button->addExtraClass('foobar');
        $button->addExtraClass('foobar');
        $button->removeExtraClass('foobar');
        $this->assertNotContains('foobar', $button->getExtraClass());
    }
}
