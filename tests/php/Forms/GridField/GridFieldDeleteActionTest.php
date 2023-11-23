<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use ReflectionMethod;
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
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\ArrayData;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->list = new DataList(Team::class);
        $config = GridFieldConfig::create()->addComponent(new GridFieldDeleteAction());
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'mockform', new FieldList([$this->gridField]), new FieldList());
    }

    public function testDontShowDeleteButtons()
    {
        if (Security::getCurrentUser()) {
            Security::setCurrentUser(null);
        }
        $content = new CSSContentParser($this->gridField->FieldHolder());
        // Check that there are content
        $this->assertEquals(4, count($content->getBySelector('.ss-gridfield-item') ?? []));
        // Make sure that there are no delete buttons
        $this->assertEquals(
            0,
            count($content->getBySelector('.gridfield-button-delete') ?? []),
            'Delete buttons should not show when not logged in.'
        );
    }

    public function testShowDeleteButtonsWithAdminPermission()
    {
        $this->logInWithPermission('ADMIN');
        $content = new CSSContentParser($this->gridField->FieldHolder());
        $deleteButtons = $content->getBySelector('.action--delete');
        $this->assertEquals(3, count($deleteButtons ?? []), 'Delete buttons should show when logged in.');
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

    public function testDeleteActionWithoutCorrectPermission()
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
                'actionName' => 'deleterecord',
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
            'User should\'t be able to delete records without correct permissions.'
        );
    }

    public function testDeleteActionWithAdminPermission()
    {
        $this->logInWithPermission('ADMIN');
        $stateID = 'testGridStateActionField';
        $session = Controller::curr()->getRequest()->getSession();
        $session->set(
            $stateID,
            [
                'grid'=>'',
                'actionName'=>'deleterecord',
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
        $this->assertEquals(2, $this->list->count(), 'User should be able to delete records with ADMIN permission.');
    }

    public function testDeleteActionRemoveRelation()
    {
        $this->logInWithPermission('ADMIN');

        $config = GridFieldConfig::create()->addComponent(new GridFieldDeleteAction(true));

        $session = Controller::curr()->getRequest()->getSession();
        $gridField = new GridField('testfield', 'testfield', $this->list, $config);
        new Form(null, 'mockform', new FieldList([$gridField]), new FieldList());
        $stateID = 'testGridStateActionField';
        $session->set(
            $stateID,
            [
                'grid'=>'',
                'actionName'=>'deleterecord',
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
        $gridField->gridFieldAlterAction(['StateID'=>$stateID], $this->form, $request);
        $this->assertEquals(2, $this->list->count(), 'User should be able to delete records with ADMIN permission.');
    }

    public function testMenuGroup()
    {
        $this->logInWithPermission('ADMIN');

        $config = GridFieldConfig::create()->addComponent($action = new GridFieldDeleteAction(true));
        $gridField = new GridField('testfield', 'testfield', $this->list, $config);
        new Form(null, 'mockform', new FieldList([$gridField]), new FieldList());
        $group = $action->getGroup($gridField, $this->list->first(), 'dummy');
        $this->assertNotNull($group, 'A menu group exists when the user can delete');

        $this->logOut();

        $group = $action->getGroup($gridField, $this->list->first(), 'dummy');
        $this->assertNull($group, 'A menu group does not exist when the user cannot delete');
    }

    public function provideHandleActionThrowsException()
    {
        return [
            'unlinks relation' => [true],
            'deletes related record' => [false],
        ];
    }

    /**
     * @dataProvider provideHandleActionThrowsException
     */
    public function testHandleActionThrowsException(bool $unlinkRelation)
    {
        $component = new GridFieldDeleteAction();
        $config = new GridFieldConfig_Base();
        $config->addComponent($component);
        $gridField = new GridField('dummy', 'dummy', new ArrayList([new ArrayData(['ID' => 1])]), $config);
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);

        $this->expectException(LogicException::class);
        $permissionMethod = $unlinkRelation ? 'canEdit' : 'canDelete';
        $this->expectExceptionMessage(
            GridFieldDeleteAction::class . " cannot be used with models that don't implement {$permissionMethod}()."
            . " Remove this component from your GridField or implement {$permissionMethod}() on $modelClass"
        );

        // Calling the method will throw an exception.
        $secondArg = $unlinkRelation ? 'unlinkrelation' : 'deleterecord';
        $component->handleAction($gridField, $secondArg, ['RecordID' => 1], []);
    }

    public function provideGetRemoveActionThrowsException()
    {
        return [
            'removes relation' => [true],
            'deletes related record' => [false],
        ];
    }

    /**
     * @dataProvider provideGetRemoveActionThrowsException
     */
    public function testGetRemoveActionThrowsException(bool $removeRelation)
    {
        $component = new GridFieldDeleteAction();
        $component->setRemoveRelation($removeRelation);
        $config = new GridFieldConfig_Base();
        $config->addComponent($component);
        $gridField = new GridField('dummy', 'dummy', new ArrayList([new ArrayData(['ID' => 1])]), $config);
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);

        $this->expectException(LogicException::class);
        $permissionMethod = $removeRelation ? 'canEdit' : 'canDelete';
        $this->expectExceptionMessage(
            GridFieldDeleteAction::class . " cannot be used with models that don't implement {$permissionMethod}()."
            . " Remove this component from your GridField or implement {$permissionMethod}() on $modelClass"
        );

        // Calling the method will throw an exception.
        $reflectionMethod = new ReflectionMethod($component, 'getRemoveAction');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invokeArgs($component, [$gridField, new ArrayData(), '']);
    }
}
