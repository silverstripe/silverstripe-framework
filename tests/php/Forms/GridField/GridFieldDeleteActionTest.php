<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridField;

class GridFieldDeleteActionTest extends SapphireTest
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
    protected static $extra_dataobjects = [
        Team::class,
        Cheerleader::class,
        Player::class,
        Permissions::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->list = new DataList(Team::class);
        $config = GridFieldConfig::create()->addComponent(new GridFieldDeleteAction());
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'mockform', new FieldList(array($this->gridField)), new FieldList());
    }

    public function testDontShowDeleteButtons()
    {
        if (Security::getCurrentUser()) {
            Security::setCurrentUser(null);
        }
        $content = new CSSContentParser($this->gridField->FieldHolder());
        // Check that there are content
        $this->assertEquals(4, count($content->getBySelector('.ss-gridfield-item')));
        // Make sure that there are no delete buttons
        $this->assertEquals(
            0,
            count($content->getBySelector('.gridfield-button-delete')),
            'Delete buttons should not show when not logged in.'
        );
    }

    public function testShowDeleteButtonsWithAdminPermission()
    {
        $this->logInWithPermission('ADMIN');
        $content = new CSSContentParser($this->gridField->FieldHolder());
        $deleteButtons = $content->getBySelector('.gridfield-button-delete');
        $this->assertEquals(3, count($deleteButtons), 'Delete buttons should show when logged in.');
    }

    public function testActionsRequireCSRF()
    {
        $this->logInWithPermission('ADMIN');
        $this->setExpectedException(
            HTTPResponse_Exception::class,
            _t(
                "SilverStripe\\Forms\\Form.CSRF_FAILED_MESSAGE",
                "There seems to have been a technical problem. Please click the back button, ".
                "refresh your browser, and try again."
            ),
            400
        );
        $stateID = 'testGridStateActionField';
        $request = new HTTPRequest(
            'POST',
            'url',
            array(),
            array(
                'action_gridFieldAlterAction?StateID='.$stateID,
                'SecurityID' => null,
            )
        );
        $this->gridField->gridFieldAlterAction(array('StateID'=>$stateID), $this->form, $request);
    }

    public function testDeleteActionWithoutCorrectPermission()
    {
        if (Security::getCurrentUser()) {
            Security::setCurrentUser(null);
        }
        $this->setExpectedException(ValidationException::class);

        $stateID = 'testGridStateActionField';
        Session::set(
            $stateID,
            array(
                'grid' => '',
                'actionName' => 'deleterecord',
                'args' => array(
                    'RecordID' => $this->idFromFixture(Team::class, 'team1')
                )
            )
        );
        $token = SecurityToken::inst();
        $request = new HTTPRequest(
            'POST',
            'url',
            array(),
            array(
                'action_gridFieldAlterAction?StateID='.$stateID => true,
                $token->getName() => $token->getValue(),
            )
        );
        $this->gridField->gridFieldAlterAction(array('StateID'=>$stateID), $this->form, $request);
        $this->assertEquals(
            3,
            $this->list->count(),
            'User should\'t be able to delete records without correct permissions.'
        );
    }

    public function testDeleteActionWithAdminPermission()
    {
        $this->logInWithPermission('ADMIN');
        $stateID = 'testGridStateActionField';
        Session::set(
            $stateID,
            array(
                'grid'=>'',
                'actionName'=>'deleterecord',
                'args' => array(
                    'RecordID' => $this->idFromFixture(Team::class, 'team1')
                )
            )
        );
        $token = SecurityToken::inst();
        $request = new HTTPRequest(
            'POST',
            'url',
            array(),
            array(
                'action_gridFieldAlterAction?StateID='.$stateID=>true,
                $token->getName() => $token->getValue(),
            )
        );
        $this->gridField->gridFieldAlterAction(array('StateID'=>$stateID), $this->form, $request);
        $this->assertEquals(2, $this->list->count(), 'User should be able to delete records with ADMIN permission.');
    }

    public function testDeleteActionRemoveRelation()
    {
        $this->logInWithPermission('ADMIN');

        $config = GridFieldConfig::create()->addComponent(new GridFieldDeleteAction(true));

        $gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $form = new Form(null, 'mockform', new FieldList(array($this->gridField)), new FieldList());

        $stateID = 'testGridStateActionField';
        Session::set(
            $stateID,
            array(
                'grid'=>'',
                'actionName'=>'deleterecord',
                'args' => array(
                    'RecordID' => $this->idFromFixture(Team::class, 'team1')
                )
            )
        );
        $token = SecurityToken::inst();
        $request = new HTTPRequest(
            'POST',
            'url',
            array(),
            array(
                'action_gridFieldAlterAction?StateID='.$stateID=>true,
                $token->getName() => $token->getValue(),
            )
        );
        $this->gridField->gridFieldAlterAction(array('StateID'=>$stateID), $this->form, $request);
        $this->assertEquals(2, $this->list->count(), 'User should be able to delete records with ADMIN permission.');
    }
}
