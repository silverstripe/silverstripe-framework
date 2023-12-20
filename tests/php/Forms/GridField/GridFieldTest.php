<?php

namespace SilverStripe\Forms\Tests\GridField;

use InvalidArgumentException;
use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridState;
use SilverStripe\Forms\GridField\GridState_Component;
use SilverStripe\Forms\GridField\GridState_Data;
use SilverStripe\Forms\GridField\GridFieldVersionTag;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Component;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Component2;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\HTMLFragments;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\Forms\Tests\ValidatorTest\TestValidator;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\VersionedGridFieldStateExtension;

class GridFieldTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        Permissions::class,
        Cheerleader::class,
        Player::class,
        Team::class,
    ];

    protected static $illegal_extensions = [
        GridFieldConfig_RecordEditor::class => [
            VersionedGridFieldStateExtension::class,
        ],
        GridFieldConfig_Base::class => [
            VersionedGridFieldStateExtension::class,
        ],
    ];

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::__construct
     */
    public function testGridField()
    {
        $obj = new GridField('testfield', 'testfield');
        $this->assertTrue($obj instanceof GridField, 'Test that the constructor arguments are valid');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::__construct
     * @covers \SilverStripe\Forms\GridField\GridField::getList
     */
    public function testGridFieldSetList()
    {
        $list = ArrayList::create([1 => 'hello', 2 => 'goodbye']);
        $obj = new GridField('testfield', 'testfield', $list);
        $this->assertEquals($list, $obj->getList(), 'Testing getList');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::__construct
     * @covers \SilverStripe\Forms\GridField\GridField::getConfig
     * @covers \SilverStripe\Forms\GridField\GridFieldConfig_Base::__construct
     * @covers \SilverStripe\Forms\GridField\GridFieldConfig::addComponent
     */
    public function testGridFieldDefaultConfig()
    {
        $obj = new GridField('testfield', 'testfield');

        $expectedComponents = new ArrayList([
            new GridFieldToolbarHeader(),
            new GridFieldButtonRow(),
            $sort = new GridFieldSortableHeader(),
            $filter = new GridFieldFilterHeader(),
            new GridFieldDataColumns(),
            new GridFieldPageCount('toolbar-header-right'),
            $pagination = new GridFieldPaginator(),
            new GridState_Component(),
            new GridFieldVersionTag(),
        ]);
        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);

        $this->assertEquals($expectedComponents, $obj->getConfig()->getComponents(), 'Testing default Config');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldConfig::__construct
     * @covers \SilverStripe\Forms\GridField\GridFieldConfig::addComponent
     */
    public function testGridFieldSetCustomConfig()
    {

        $config = GridFieldConfig::create();
        $config->addComponent(new GridFieldSortableHeader());
        $config->addComponent(new GridFieldDataColumns());

        $obj = new GridField('testfield', 'testfield', ArrayList::create([]), $config);

        $expectedComponents = new ArrayList(
            [
            0 => new GridFieldSortableHeader,
            1 => new GridFieldDataColumns,
            2 => new GridState_Component,
            3 => new GridFieldVersionTag,
            ]
        );

        $this->assertEquals($expectedComponents, $obj->getConfig()->getComponents(), 'Testing default Config');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getModelClass
     * @covers \SilverStripe\Forms\GridField\GridField::setModelClass
     */
    public function testGridFieldModelClass()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        $this->assertEquals(Member::class, $obj->getModelClass(), 'Should return Member');
        $obj->setModelClass(Group::class);
        $this->assertEquals(Group::class, $obj->getModelClass(), 'Should return Group');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getModelClass
     */
    public function testGridFieldModelClassThrowsException()
    {
        $this->expectException(LogicException::class);
        $obj = new GridField('testfield', 'testfield', ArrayList::create());
        $obj->getModelClass();
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::setList
     * @covers \SilverStripe\Forms\GridField\GridField::getList
     */
    public function testSetAndGetList()
    {
        $list = Member::get();
        $arrayList = ArrayList::create([1, 2, 3]);
        $obj = new GridField('testfield', 'testfield', $list);
        $this->assertEquals($list, $obj->getList());
        $obj->setList($arrayList);
        $this->assertEquals($arrayList, $obj->getList());
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getState
     */
    public function testGetState()
    {
        $obj = new GridField('testfield', 'testfield');
        $this->assertTrue($obj->getState() instanceof GridState_Data);
        $this->assertTrue($obj->getState(false) instanceof GridState);
    }

    /**
     * Tests usage of nested GridState values
     *
     * @covers \SilverStripe\Forms\GridField\GridState_Data::__get
     * @covers \SilverStripe\Forms\GridField\GridState_Data::__call
     * @covers \SilverStripe\Forms\GridField\GridState_Data::getData
     */
    public function testGetStateData()
    {
        $obj = new GridField('testfield', 'testfield');

        // Check value persistence
        $this->assertEquals(15, $obj->State->NoValue(15));
        $this->assertEquals(15, $obj->State->NoValue(-1));
        $obj->State->NoValue = 10;
        $this->assertEquals(10, $obj->State->NoValue);
        $this->assertEquals(10, $obj->State->NoValue(20));

        // Test that values can be set, unset, and inspected
        $this->assertFalse(isset($obj->State->NotSet));
        $obj->State->NotSet = false;
        $this->assertTrue(isset($obj->State->NotSet));
        unset($obj->State->NotSet);
        $this->assertFalse(isset($obj->State->NotSet));

        // Test that false evaluating values are storable
        $this->assertEquals(0, $obj->State->Falsey0(0)); // expect 0 back
        $this->assertEquals(0, $obj->State->Falsey0(10)); // expect 0 back
        $this->assertEquals(0, $obj->State->Falsey0); //expect 0 back
        $obj->State->Falsey0 = 0; //manually assign 0
        $this->assertEquals(0, $obj->State->Falsey0); //expect 0 back

        // Test that false is storable
        $this->assertFalse($obj->State->Falsey2(false));
        $this->assertFalse($obj->State->Falsey2(true));
        $this->assertFalse($obj->State->Falsey2);
        $obj->State->Falsey2 = false;
        $this->assertFalse($obj->State->Falsey2);

        // Check nested values
        $this->assertInstanceOf('SilverStripe\\Forms\\GridField\\GridState_Data', $obj->State->Nested);
        $this->assertInstanceOf('SilverStripe\\Forms\\GridField\\GridState_Data', $obj->State->Nested->DeeperNested());
        $this->assertEquals(3, $obj->State->Nested->DataValue(3));
        $this->assertEquals(10, $obj->State->Nested->DeeperNested->DataValue(10));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumns
     */
    public function testGetColumns()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        $expected = [
            0 => 'FirstName',
            1 => 'Surname',
            2 => 'Email',
        ];
        $this->assertEquals($expected, $obj->getColumns());
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnCount
     */
    public function testGetColumnCount()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        $this->assertEquals(3, $obj->getColumnCount());
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnContent
     */
    public function testGetColumnContent()
    {
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $obj = new GridField('testfield', 'testfield', $list);
        $this->assertEquals('test@example.org', $obj->getColumnContent($list->first(), 'Email'));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnContent
     */
    public function testGetColumnContentBadArguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $obj = new GridField('testfield', 'testfield', $list);
        $obj->getColumnContent($list->first(), 'non-existing');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnAttributes
     */
    public function testGetColumnAttributesEmptyArray()
    {
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $obj = new GridField('testfield', 'testfield', $list);
        $this->assertEquals(['class' => 'col-Email'], $obj->getColumnAttributes($list->first(), 'Email'));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnAttributes
     */
    public function testGetColumnAttributes()
    {
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $config = GridFieldConfig::create()->addComponent(new Component);
        $obj = new GridField('testfield', 'testfield', $list, $config);
        $this->assertEquals(['class' => 'css-class'], $obj->getColumnAttributes($list->first(), 'Email'));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnAttributes
     */
    public function testGetColumnAttributesBadArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $config = GridFieldConfig::create()->addComponent(new Component);
        $obj = new GridField('testfield', 'testfield', $list, $config);
        $obj->getColumnAttributes($list->first(), 'Non-existing');
    }

    public function testGetColumnAttributesBadResponseFromComponent()
    {
        $this->expectException(\LogicException::class);
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $config = GridFieldConfig::create()->addComponent(new Component);
        $obj = new GridField('testfield', 'testfield', $list, $config);
        $obj->getColumnAttributes($list->first(), 'Surname');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnMetadata
     */
    public function testGetColumnMetadata()
    {
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $config = GridFieldConfig::create()->addComponent(new Component);
        $obj = new GridField('testfield', 'testfield', $list, $config);
        $this->assertEquals(['metadata' => 'istrue'], $obj->getColumnMetadata('Email'));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnMetadata
     */
    public function testGetColumnMetadataBadResponseFromComponent()
    {
        $this->expectException(\LogicException::class);
        $list = new ArrayList(
            [
            new Member(["ID" => 1, "Email" => "test@example.org"])
            ]
        );
        $config = GridFieldConfig::create()->addComponent(new Component);
        $obj = new GridField('testfield', 'testfield', $list, $config);
        $obj->getColumnMetadata('Surname');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getColumnMetadata
     */
    public function testGetColumnMetadataBadArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $list = ArrayList::create();
        $config = GridFieldConfig::create()->addComponent(new Component);
        $obj = new GridField('testfield', 'testfield', $list, $config);
        $obj->getColumnMetadata('non-exist-qweqweqwe');
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::handleAction
     */
    public function testHandleActionBadArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $obj = new GridField('testfield', 'testfield');
        $obj->handleAlterAction('prft', [], []);
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::handleAction
     */
    public function testHandleAction()
    {
        $config = GridFieldConfig::create()->addComponent(new Component);
        $obj = new GridField('testfield', 'testfield', ArrayList::create(), $config);
        $this->assertEquals('handledAction is executed', $obj->handleAlterAction('jump', [], []));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getCastedValue
     */
    public function testGetCastedValue()
    {
        $obj = new GridField('testfield', 'testfield');
        $value = $obj->getCastedValue('This is a sentence. This ia another.', ['Text->FirstSentence']);
        $this->assertEquals('This is a sentence.', $value);
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::getCastedValue
     */
    public function testGetCastedValueObject()
    {
        $obj = new GridField('testfield', 'testfield');
        $value = $obj->getCastedValue('Here is some <html> content', 'Text');
        $this->assertEquals('Here is some &lt;html&gt; content', $value);
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::gridFieldAlterAction
     */
    public function testGridFieldAlterAction()
    {
        $this->markTestIncomplete();

        // $config = GridFieldConfig::create()->addComponent(new GridFieldTest_Component);
        // $obj = new GridField('testfield', 'testfield', ArrayList::create(), $config);
        // $id = 'testGridStateActionField';
        // Session::set($id, array('grid'=>'', 'actionName'=>'jump'));
        // $form = new Form(null, 'mockform', new FieldList(array($obj)), new FieldList());
        // $request = new HTTPRequest('POST', 'url');
        // $obj->gridFieldAlterAction(array('StateID'=>$id), $form, $request);
    }

    /**
     * Test the interface for adding custom HTML fragment slots via a component
     */
    public function testGridFieldCustomFragments()
    {

        new HTMLFragments(
            [
            "header-left-actions" => "left\$DefineFragment(nested-left)",
            "header-right-actions" => "right",
            ]
        );

        new HTMLFragments(
            [
            "nested-left" => "[inner]",
            ]
        );


        $config = GridFieldConfig::create()->addComponents(
            new HTMLFragments(
                [
                "header" => "<tr><td><div class=\"right\">\$DefineFragment(header-right-actions)</div>"
                    . "<div class=\"left\">\$DefineFragment(header-left-actions)</div></td></tr>",
                ]
            ),
            new HTMLFragments(
                [
                "header-left-actions" => "left",
                "header-right-actions" => "rightone",
                ]
            ),
            new HTMLFragments(
                [
                "header-right-actions" => "righttwo",
                ]
            )
        );
        $field = new GridField('testfield', 'testfield', ArrayList::create(), $config);
        $form = new Form(null, 'testform', new FieldList([$field]), new FieldList());

        $this->assertStringContainsString(
            "<div class=\"right\">rightone\nrighttwo</div><div class=\"left\">left</div>",
            $field->FieldHolder()
        );
    }

    /**
     * Test the nesting of custom fragments
     */
    public function testGridFieldCustomFragmentsNesting()
    {
        $config = GridFieldConfig::create()->addComponents(
            new HTMLFragments(
                [
                "level-one" => "first",
                ]
            ),
            new HTMLFragments(
                [
                "before" => "<div>\$DefineFragment(level-one)</div>",
                ]
            ),
            new HTMLFragments(
                [
                "level-one" => "<strong>\$DefineFragment(level-two)</strong>",
                ]
            ),
            new HTMLFragments(
                [
                "level-two" => "second",
                ]
            )
        );
        $field = new GridField('testfield', 'testfield', ArrayList::create(), $config);
        $form = new Form(null, 'testform', new FieldList([$field]), new FieldList());

        $this->assertStringContainsString(
            "<div>first\n<strong>second</strong></div>",
            $field->FieldHolder()
        );
    }

    /**
     * Test that circular dependencies throw an exception
     */
    public function testGridFieldCustomFragmentsCircularDependencyThrowsException()
    {
        $this->expectException(\LogicException::class);
        $config = GridFieldConfig::create()->addComponents(
            new HTMLFragments(
                [
                "level-one" => "first",
                ]
            ),
            new HTMLFragments(
                [
                "before" => "<div>\$DefineFragment(level-one)</div>",
                ]
            ),
            new HTMLFragments(
                [
                "level-one" => "<strong>\$DefineFragment(level-two)</strong>",
                ]
            ),
            new HTMLFragments(
                [
                "level-two" => "<blink>\$DefineFragment(level-one)</blink>",
                ]
            )
        );
        $field = new GridField('testfield', 'testfield', ArrayList::create(), $config);
        $form = new Form(null, 'testform', new FieldList([$field]), new FieldList());

        $field->FieldHolder();
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridField::FieldHolder
     */
    public function testCanViewOnlyOddIDs()
    {
        $this->logInWithPermission();
        $list = new ArrayList(
            [
            new Permissions(
                [
                "ID" => 1,
                "Email" => "ongi.schwimmer@example.org",
                'Name' => 'Ongi Schwimmer'
                ]
            ),
            new Permissions(
                [
                "ID" => 2,
                "Email" => "klaus.lozenge@example.org",
                'Name' => 'Klaus Lozenge'
                ]
            ),
            new Permissions(
                [
                "ID" => 3,
                "Email" => "otto.fischer@example.org",
                'Name' => 'Otto Fischer'
                ]
            )
            ]
        );

        $config = new GridFieldConfig();
        $config->addComponent(new GridFieldDataColumns());
        $obj = new GridField('testfield', 'testfield', $list, $config);
        $form = new Form(null, 'mockform', new FieldList([$obj]), new FieldList());
        $content = new CSSContentParser($obj->FieldHolder());

        $members = $content->getBySelector('.ss-gridfield-item tr');

        $this->assertEquals(2, count($members ?? []));

        $this->assertEquals(
            (string)$members[0]->td[0],
            'Ongi Schwimmer',
            'First object Name should be Ongi Schwimmer'
        );
        $this->assertEquals(
            (string)$members[0]->td[1],
            'ongi.schwimmer@example.org',
            'First object Email should be ongi.schwimmer@example.org'
        );

        $this->assertEquals(
            (string)$members[1]->td[0],
            'Otto Fischer',
            'Second object Name should be Otto Fischer'
        );
        $this->assertEquals(
            (string)$members[1]->td[1],
            'otto.fischer@example.org',
            'Second object Email should be otto.fischer@example.org'
        );
    }

    public function testChainedDataManipulators()
    {
        $config = new GridFieldConfig();
        $data = new ArrayList([1, 2, 3, 4, 5, 6]);
        $gridField = new GridField('testfield', 'testfield', $data, $config);
        $endList = $gridField->getManipulatedList();
        $this->assertEquals($endList->count(), 6);

        $config->addComponent(new Component2);
        $endList = $gridField->getManipulatedList();
        $this->assertEquals($endList->count(), 12);

        $config->addComponent(new GridFieldPaginator(10));
        $endList = $gridField->getManipulatedList();
        $this->assertEquals($endList->count(), 10);
    }

    public function testValidationMessageInOutput()
    {
        $gridField = new GridField('testfield', 'testfield', new ArrayList(), new GridFieldConfig());
        $fieldList = new FieldList([$gridField]);
        $validator = new TestValidator();
        $form = new Form(null, "testForm", $fieldList, new FieldList(), $validator);

        // A form that fails validation should display the validation error in the FieldHolder output.
        $form->validationResult();
        $gridfieldOutput = $gridField->FieldHolder();
        $this->assertStringContainsString('<p class="message ' . ValidationResult::TYPE_ERROR . '">error</p>', $gridfieldOutput);

        // Clear validation error from previous assertion.
        $validator->removeValidation();
        $gridField->setMessage(null);

        // A form that passes validation should not display a validation error in the FieldHolder output.
        $form->setValidator(new RequiredFields());
        $form->validationResult();
        $gridfieldOutput = $gridField->FieldHolder();
        $this->assertStringNotContainsString('<p class="message ' . ValidationResult::TYPE_ERROR . '">', $gridfieldOutput);
    }

    public function testAddRequestToURL()
    {
        $gridField = new GridField('testfield', 'testfield');
        $link = Controller::join_links('class-name', 'item', '1');

        // The actual URL path here is arbitrary
        $request = new HTTPRequest(
            'POST',
            'admin/gridfield',
            [
                'gridState-Test-0' => [
                    'State' => [
                        'Column' => 'Name'
                    ],
                ]
            ],
            [
                'action_gridFieldAlterAction?StateID=1',
                'ActionState' => json_encode([
                    'grid' => $gridField->getName(),
                    'actionName' => 'edit',
                    'args' => [],
                ]),
                'gridState-Test-1' => [
                    'State' => [
                        'Column' => 'Name'
                    ],
                ]
            ]
        );

        $request->setRouteParams(['Action' => 'edit']);
        $gridField->setRequest($request);

        $this->assertEquals(
            $gridField->addAllStateToUrl($link),
            '/class-name/item/1?gridState-Test-0%5BState%5D%5BColumn%5D=Name&gridState-Test-1%5BState%5D%5BColumn%5D=Name'
        );
        // The actual URL path here is arbitrary
        $request = new HTTPRequest(
            'GET',
            'admin/gridfield',
            [
                'action_gridFieldAlterAction?StateID=1',
                'ActionState' => json_encode([
                    'grid' => $gridField->getName(),
                    'actionName' => 'edit',
                    'args' => [],
                ]),
                'gridState-Test' => [
                    'State' => [
                        'Column' => 'Name'
                    ],
                ]
            ]
        );
        $request->setRouteParams(['Action' => 'edit']);
        $gridField->setRequest($request);

        $this->assertEquals(
            $gridField->addAllStateToUrl($link),
            '/class-name/item/1?gridState-Test%5BState%5D%5BColumn%5D=Name'
        );
    }
}
