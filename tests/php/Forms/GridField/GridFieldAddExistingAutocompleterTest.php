<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\Tests\GridField\GridFieldAddExistingAutocompleterTest\TestController;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Stadium;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class GridFieldAddExistingAutocompleterTest extends FunctionalTest
{

    protected static $fixture_file = 'GridFieldTest.yml';

    protected static $extra_dataobjects = [
        Team::class,
        Cheerleader::class,
        Player::class,
        Permissions::class,
        Stadium::class,
    ];

    protected static $extra_controllers = [
        TestController::class,
    ];

    public function testScaffoldSearchFields()
    {
        $autoCompleter = new GridFieldAddExistingAutocompleter($targetFragment = 'before', ['Test']);
        $this->assertEquals(
            [
                'Name:StartsWith',
                'City:EndsWith',
                'Country:ExactMatch',
                'Type:Fulltext'
            ],
            $autoCompleter->scaffoldSearchFields(Stadium::class)
        );
    }

    function testSearch()
    {
        $team2 = $this->objFromFixture(Team::class, 'team2');

        $response = $this->get('GridFieldAddExistingAutocompleterTest_Controller');
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $btns = $parser->getBySelector('.grid-field .action_gridfield_relationfind');

        $response = $this->post(
            'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/search'
                . '/?gridfield_relationsearch=Team 2',
            [(string)$btns[0]['name'] => 1]
        );
        $this->assertFalse($response->isError());
        $result = json_decode($response->getBody() ?? '', true);
        $this->assertEquals(1, count($result ?? []));
        $this->assertEquals(
            [[
            'label' => 'Team 2',
            'value' => 'Team 2',
            'id' => $team2->ID,
            ]],
            $result
        );

        $response = $this->post(
            'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/'
                . 'search/?gridfield_relationsearch=Heather',
            [(string)$btns[0]['name'] => 1]
        );
        $this->assertFalse($response->isError());
        $result = json_decode($response->getBody() ?? '', true);
        $this->assertEquals(1, count($result ?? []), "The relational filter did not work");

        $response = $this->post(
            'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/search'
                . '/?gridfield_relationsearch=Unknown',
            [(string)$btns[0]['name'] => 1]
        );
        $this->assertFalse($response->isError());
        $result = json_decode($response->getBody() ?? '', true);
        $this->assertEmpty($result, 'The output is either an empty array or boolean FALSE');
    }

    public function testAdd()
    {
        $this->logInWithPermission('ADMIN');
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $team2 = $this->objFromFixture(Team::class, 'team2');

        $response = $this->get('GridFieldAddExistingAutocompleterTest_Controller');
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $items = $parser->getBySelector('.grid-field .ss-gridfield-items .ss-gridfield-item');
        $this->assertEquals(1, count($items ?? []));
        $this->assertEquals($team1->ID, (int)$items[0]['data-id']);

        $btns = $parser->getBySelector('.grid-field .action_gridfield_relationadd');
        $response = $this->post(
            'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield',
            [
                'relationID' => $team2->ID,
                (string)$btns[0]['name'] => 1
            ]
        );
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $items = $parser->getBySelector('.grid-field .ss-gridfield-items .ss-gridfield-item');
        $this->assertEquals(2, count($items ?? []));
        $this->assertListEquals(
            [
            ['ID' => (int)$items[0]['data-id']],
            ['ID' => (int)$items[1]['data-id']],
            ],
            new ArrayList([$team1, $team2])
        );
    }

    public function testRetainsCustomSort()
    {
        $component = new GridFieldAddExistingAutocompleter($targetFragment = 'before', ['Test']);
        $component->setSearchFields(['Name']);

        $grid = $this->getGridFieldForComponent($component);
        $grid->setList(Team::get()->filter('Name', 'force-empty-list'));

        $component->setSearchList(Team::get());
        $request = new HTTPRequest('GET', '', ['gridfield_relationsearch' => 'Team']);
        $response = $component->doSearch($grid, $request);
        $this->assertFalse($response->isError());
        $result = json_decode($response->getBody() ?? '', true);
        $this->assertEquals(
            ['Team 1', 'Team 2', 'Team 3', 'Team 4'],
            array_map(
                function ($item) {
                    return $item['label'];
                },
                $result ?? []
            )
        );

        $component->setSearchList(Team::get()->sort('Name', 'DESC'));
        $request = new HTTPRequest('GET', '', ['gridfield_relationsearch' => 'Team']);
        $response = $component->doSearch($grid, $request);
        $this->assertFalse($response->isError());
        $result = json_decode($response->getBody() ?? '', true);
        $this->assertEquals(
            ['Team 4', 'Team 3', 'Team 2', 'Team 1'],
            array_map(
                function ($item) {
                    return $item['label'];
                },
                $result ?? []
            )
        );
    }

    public function testGetHTMLFragmentsNeedsDataObject()
    {
        $component = new GridFieldAddExistingAutocompleter();
        $gridField = $this->getGridFieldForComponent($component);
        $list = new ArrayList();
        $dataClass = ArrayData::class;
        $list->setDataClass($dataClass);
        $gridField->setList($list);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            GridFieldAddExistingAutocompleter::class
            . " must be used with DataObject subclasses. Found '$dataClass'"
        );
        // Calling the method will throw an exception.
        $component->getHTMLFragments($gridField);
    }

    public function testGetManipulatedDataNeedsDataObject()
    {
        $component = new GridFieldAddExistingAutocompleter();
        $gridField = $this->getGridFieldForComponent($component);
        $list = new ArrayList();
        $dataClass = ArrayData::class;
        $list->setDataClass($dataClass);
        $gridField->setList($list);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            GridFieldAddExistingAutocompleter::class
            . " must be used with DataObject subclasses. Found '$dataClass'"
        );

        // Calling the method will throw an exception.
        $component->getManipulatedData($gridField, $list);
    }

    public function testDoSearchNeedsDataObject()
    {
        $component = new GridFieldAddExistingAutocompleter();
        $gridField = $this->getGridFieldForComponent($component);
        $list = new ArrayList();
        $dataClass = ArrayData::class;
        $list->setDataClass($dataClass);
        $gridField->setList($list);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            GridFieldAddExistingAutocompleter::class
            . " must be used with DataObject subclasses. Found '$dataClass'"
        );

        // Calling the method will throw an exception.
        $component->doSearch($gridField, new HTTPRequest('GET', ''));
    }

    public function testScaffoldSearchFieldsNeedsDataObject()
    {
        $component = new GridFieldAddExistingAutocompleter();
        $gridField = $this->getGridFieldForComponent($component);
        $list = new ArrayList();
        $dataClass = ArrayData::class;
        $list->setDataClass($dataClass);
        $gridField->setList($list);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            GridFieldAddExistingAutocompleter::class
            . " must be used with DataObject subclasses. Found '$dataClass'"
        );

        // Calling the method will either throw an exception or not.
        // The test pass/failure is explicitly about whether an exception is thrown.
        $component->scaffoldSearchFields($dataClass);
    }

    public function testGetPlaceholderTextNeedsDataObject()
    {
        $component = new GridFieldAddExistingAutocompleter();
        $gridField = $this->getGridFieldForComponent($component);
        $list = new ArrayList();
        $dataClass = ArrayData::class;
        $list->setDataClass($dataClass);
        $gridField->setList($list);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            GridFieldAddExistingAutocompleter::class
            . " must be used with DataObject subclasses. Found '$dataClass'"
        );

        // Calling the method will either throw an exception or not.
        // The test pass/failure is explicitly about whether an exception is thrown.
        $component->getPlaceholderText($dataClass);
    }

    public function testSetPlaceholderTextDoesntNeedDataObject()
    {
        $component = new GridFieldAddExistingAutocompleter();
        $gridField = $this->getGridFieldForComponent($component);
        $list = new ArrayList();
        $dataClass = ArrayData::class;
        $list->setDataClass($dataClass);
        $gridField->setList($list);

        // Prevent from being marked risky.
        // This test passes if there's no exception thrown.
        $this->expectNotToPerformAssertions();

        $component->setPlaceholderText('');
    }

    protected function getGridFieldForComponent($component)
    {
        $config = GridFieldConfig::create()->addComponents(
            $component,
            new GridFieldDataColumns()
        );

        return (new GridField('testfield', 'testfield'))
            ->setConfig($config);
    }
}
