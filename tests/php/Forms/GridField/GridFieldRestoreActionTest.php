<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Session;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldRestoreAction;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Versioned\Versioned;

class GridFieldRestoreActionTest extends SapphireTest
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
        $this->list->each(function ($item) {
            $item->doArchive();
        });
        $this->list = Versioned::get_including_deleted(Team::class);
        $this->list = $this->list->filterByCallback(function ($item) {
            // Doesn't exist on either stage or live
            return $item->isArchived();
        });
        $config = GridFieldConfig::create()->addComponent(new GridFieldRestoreAction());
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'mockform', new FieldList([$this->gridField]), new FieldList());
    }

    public function testDontShowRestoreButtons()
    {
        if (Security::getCurrentUser()) {
            Security::setCurrentUser(null);
        }
        $content = new CSSContentParser($this->gridField->FieldHolder());
        // Check that there are content
        $this->assertEquals(4, count($content->getBySelector('.ss-gridfield-item')));
        // Make sure that there are no restore buttons
        $this->assertEquals(
            0,
            count($content->getBySelector('.action-restore')),
            'Restore buttons should not show when not logged in.'
        );
    }

    public function testShowRestoreButtonsWithAdminPermission()
    {
        $this->logInWithPermission('ADMIN');
        $content = new CSSContentParser($this->gridField->FieldHolder());
        $restoreButtons = $content->getBySelector('.action-restore');
        $this->assertEquals(3, count($restoreButtons), 'Restore buttons should show when logged in.');
    }

    public function testActionsRequireCSRF()
    {
        $this->logInWithPermission('ADMIN');
        $this->expectException(HTTPResponse_Exception::class);
        $this->expectExceptionMessage(_t(
            "SilverStripe\\Forms\\Form.CSRF_FAILED_MESSAGE",
            "There seems to have been a technical problem. Please click the back button, " . "refresh your browser, and try again."
        ));
        $this->expectExceptionCode(400);
        $stateID = 'testGridStateActionField';
        $request = new HTTPRequest(
            'POST',
            'url',
            [],
            [
                'action_gridFieldAlterAction?StateID=' . $stateID,
                'SecurityID' => null,
            ]
        );
        $request->setSession(new Session([]));
        $this->gridField->gridFieldAlterAction(['StateID'=>$stateID], $this->form, $request);
    }

    public function testRestoreActionWithoutCorrectPermission()
    {
        if (Security::getCurrentUser()) {
            Security::setCurrentUser(null);
        }
        $this->expectException(ValidationException::class);

        $stateID = 'testGridStateActionField';
        $session = Controller::curr()->getRequest()->getSession();
        $session->set(
            $stateID,
            [
                'grid' => '',
                'actionName' => 'restore',
                'args' => [
                    'RecordID' => $this->idFromFixture(Team::class, 'team1')
                ]
            ]
        );
        $token = SecurityToken::inst();
        $request = new HTTPRequest(
            'POST',
            'url',
            [],
            [
                'action_gridFieldAlterAction?StateID=' . $stateID => true,
                $token->getName() => $token->getValue(),
            ]
        );
        $request->setSession($session);
        $this->gridField->gridFieldAlterAction(['StateID'=>$stateID], $this->form, $request);
        $this->assertEquals(
            3,
            $this->list->count(),
            'User should\'t be able to restore records without correct permissions.'
        );
    }

    public function testRestoreActionWithAdminPermission()
    {
        $member = $this->objFromFixture(Member::class, 'admin');
        Security::setCurrentUser($member);
        $stateID = 'testGridStateActionField';
        $session = Controller::curr()->getRequest()->getSession();
        $session->set(
            $stateID,
            [
                'grid'=>'',
                'actionName'=>'restore',
                'args' => [
                    'RecordID' => $this->idFromFixture(Team::class, 'team1')
                ]
            ]
        );
        $token = SecurityToken::inst();
        $request = new HTTPRequest(
            'POST',
            'url',
            [],
            [
                'action_gridFieldAlterAction?StateID=' . $stateID=>true,
                $token->getName() => $token->getValue(),
            ]
        );
        $request->setSession($session);
        $this->gridField->gridFieldAlterAction(['StateID'=>$stateID], $this->form, $request);
        $this->assertEquals(2, $this->list->count(), 'User should be able to restore records with ADMIN permission.');
    }
}
