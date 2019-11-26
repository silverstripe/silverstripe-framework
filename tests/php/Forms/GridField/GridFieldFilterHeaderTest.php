<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest\CheerleaderHat;
use SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest\Mom;
use SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest\Team;
use SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest\TeamGroup;
use SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest\TestController;
use SilverStripe\ORM\DataList;

class GridFieldFilterHeaderTest extends SapphireTest
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
     * @var GridFieldFilterHeader
     */
    protected $component;

    protected static $fixture_file = 'GridFieldFilterHeaderTest.yml';

    protected static $extra_dataobjects = [
        Team::class,
        TeamGroup::class,
        Cheerleader::class,
        CheerleaderHat::class,
        Mom::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->list = new DataList(Team::class);
        $config = GridFieldConfig_RecordEditor::create()->addComponent(new GridFieldFilterHeader());
        $this->gridField = new GridField('testfield', 'testfield', $this->list, $config);
        $this->form = new Form(null, 'Form', new FieldList([$this->gridField]), new FieldList());
        $this->component = $this->gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
    }

    /**
     * Tests that the appropriate filter headers are generated
     *
     * @skipUpgrade
     */
    public function testRenderHeaders()
    {
        $htmlFragment = $this->component->getHTMLFragments($this->gridField);

        // Check that the output is the new search field
        $this->assertStringContainsString('<div class="search-holder grid-field__search-holder grid-field__search-holder--hidden"', $htmlFragment['before']);
        $this->assertStringContainsString('Open search and filter', $htmlFragment['buttons-before-right']);

        $this->gridField->getConfig()->removeComponentsByType(GridFieldFilterHeader::class);
        $this->gridField->getConfig()->addComponent(new GridFieldFilterHeader(true));
        $this->component = $this->gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
        $htmlFragment = $this->component->getHTMLFragments($this->gridField);

        // Check that the output is the legacy filter header
        $this->assertStringContainsString(
            '<tr class="grid-field__filter-header grid-field__search-holder--hidden">',
            $htmlFragment['header']
        );
        $this->assertFalse(array_key_exists('buttons-before-right', $htmlFragment ?? []));
    }

    public function testSearchFieldSchema()
    {
        $searchSchema = json_decode($this->component->getSearchFieldSchema($this->gridField) ?? '');

        $this->assertEquals('field/testfield/schema/SearchForm', $searchSchema->formSchemaUrl);
        $this->assertEquals('Name', $searchSchema->name);
        $this->assertEquals('Search "Teams"', $searchSchema->placeholder);
        $this->assertEquals(new \stdClass, $searchSchema->filters);

        $request = new HTTPRequest(
            'POST',
            'field/testfield',
            [],
            [
                'filter' => [
                    'testfield' => [
                        'Name' => 'test',
                        'City' => 'place'
                    ]
                ],
            ]
        );
        $this->gridField->setRequest($request);
        $searchSchema = json_decode($this->component->getSearchFieldSchema($this->gridField) ?? '');

        $this->assertEquals('field/testfield/schema/SearchForm', $searchSchema->formSchemaUrl);
        $this->assertEquals('Name', $searchSchema->name);
        $this->assertEquals('Search "Teams"', $searchSchema->placeholder);
        $this->assertEquals('test', $searchSchema->filters->Search__Name);
        $this->assertEquals('place', $searchSchema->filters->Search__City);
        $this->assertEquals('testfield', $searchSchema->gridfield);
    }

    public function testHandleActionReset()
    {
        // Init Grid state with some pre-existing filters
        $state = $this->gridField->State;
        $state->GridFieldFilterHeader = [];
        $state->GridFieldFilterHeader->Columns = [];
        $state->GridFieldFilterHeader->Columns->Name = 'test';

        $this->component->handleAction(
            $this->gridField,
            'reset',
            [],
            '{"GridFieldFilterHeader":{"Columns":{"Name":"test"}}}'
        );

        $this->assertEmpty(
            $state->GridFieldFilterHeader->Columns->toArray(),
            'GridFieldFilterHeader::handleAction resets the gridstate filter when the user resets the search.'
        );
    }

    public function testGetSearchForm()
    {
        $searchForm = $this->component->getSearchForm($this->gridField);

        $this->assertTrue($searchForm instanceof Form);
        $this->assertEquals('Search__Name', $searchForm->fields[0]->Name);
        $this->assertEquals('Search__City', $searchForm->fields[1]->Name);
        $this->assertEquals('Search__Cheerleader__Hat__Colour', $searchForm->fields[2]->Name);
        $this->assertEquals('TeamsSearchForm', $searchForm->Name);
        $this->assertEquals('cms-search-form', $searchForm->extraClasses['cms-search-form']);

        foreach ($searchForm->fields as $field) {
            $this->assertEquals('stacked', $field->extraClasses['stacked']);
            $this->assertEquals('no-change-track', $field->extraClasses['no-change-track']);
        }
    }

    public function testCustomSearchField()
    {
        $searchSchema = json_decode($this->component->getSearchFieldSchema($this->gridField));
        $this->assertEquals('Name', $searchSchema->name);

        Config::modify()->set(Team::class, 'general_search_field', 'CustomSearch');
        $searchSchema = json_decode($this->component->getSearchFieldSchema($this->gridField));
        $this->assertEquals('CustomSearch', $searchSchema->name);

        $this->component->setSearchField('ReallyCustomSearch');
        $searchSchema = json_decode($this->component->getSearchFieldSchema($this->gridField));
        $this->assertEquals('ReallyCustomSearch', $searchSchema->name);

        $this->assertEquals('ReallyCustomSearch', $this->component->getSearchField());
    }
}
