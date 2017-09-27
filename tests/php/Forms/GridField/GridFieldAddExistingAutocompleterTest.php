<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\Tests\GridField\GridFieldAddExistingAutocompleterTest\TestController;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Permissions;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Player;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;

/**
 * @skipUpgrade
 */
class GridFieldAddExistingAutocompleterTest extends FunctionalTest
{

    protected static $fixture_file = 'GridFieldTest.yml';

    protected static $extra_dataobjects = [
        Team::class,
        Cheerleader::class,
        Player::class,
        Permissions::class
    ];

    protected static $extra_controllers = [
        TestController::class,
    ];

    public function testScaffoldSearchFields()
    {
        $autoCompleter = new GridFieldAddExistingAutocompleter($targetFragment = 'before', array('Test'));
        $this->assertEquals(
            array(
                'Name:PartialMatch',
                'City:StartsWith',
                'Cheerleaders.Name:StartsWith'
            ),
            $autoCompleter->scaffoldSearchFields(Team::class)
        );
        $this->assertEquals(
            [ 'Name:StartsWith' ],
            $autoCompleter->scaffoldSearchFields(Cheerleader::class)
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
            array((string)$btns[0]['name'] => 1)
        );
        $this->assertFalse($response->isError());
        $result = Convert::json2array($response->getBody());
        $this->assertEquals(1, count($result));
        $this->assertEquals(
            array(array(
            'label' => 'Team 2',
            'value' => 'Team 2',
            'id' => $team2->ID,
            )),
            $result
        );

        $response = $this->post(
            'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/'
                . 'search/?gridfield_relationsearch=Heather',
            array((string)$btns[0]['name'] => 1)
        );
        $this->assertFalse($response->isError());
        $result = Convert::json2array($response->getBody());
        $this->assertEquals(1, count($result), "The relational filter did not work");

        $response = $this->post(
            'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield/search'
                . '/?gridfield_relationsearch=Unknown',
            array((string)$btns[0]['name'] => 1)
        );
        $this->assertFalse($response->isError());
        $result = Convert::json2array($response->getBody());
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
        $this->assertEquals(1, count($items));
        $this->assertEquals($team1->ID, (int)$items[0]['data-id']);

        $btns = $parser->getBySelector('.grid-field .action_gridfield_relationadd');
        $response = $this->post(
            'GridFieldAddExistingAutocompleterTest_Controller/Form/field/testfield',
            array(
                'relationID' => $team2->ID,
                (string)$btns[0]['name'] => 1
            )
        );
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $items = $parser->getBySelector('.grid-field .ss-gridfield-items .ss-gridfield-item');
        $this->assertEquals(2, count($items));
        $this->assertListEquals(
            array(
            array('ID' => (int)$items[0]['data-id']),
            array('ID' => (int)$items[1]['data-id']),
            ),
            new ArrayList(array($team1, $team2))
        );
    }
}
