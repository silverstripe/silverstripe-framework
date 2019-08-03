<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Control\HTTPRequest;
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

    protected static $extra_dataobjects = array(
        Team::class,
        TeamGroup::class,
        Cheerleader::class,
        CheerleaderHat::class,
        Mom::class,
    );

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
        $this->assertContains(
            '<div class="search-holder grid-field__search-holder grid-field__search-holder--hidden"',
            $htmlFragment['before']
        );
        $this->assertContains('Open search and filter', $htmlFragment['buttons-before-right']);

        $this->gridField->getConfig()->removeComponentsByType(GridFieldFilterHeader::class);
        $this->gridField->getConfig()->addComponent(new GridFieldFilterHeader(true));
        $this->component = $this->gridField->getConfig()->getComponentByType(GridFieldFilterHeader::class);
        $htmlFragment = $this->component->getHTMLFragments($this->gridField);

        // Check that the output is the legacy filter header
        $this->assertContains(
            '<tr class="grid-field__filter-header grid-field__search-holder--hidden">',
            $htmlFragment['header']
        );
        $this->assertFalse(array_key_exists('buttons-before-right', $htmlFragment));
    }

    public function testSearchFieldSchema()
    {
        $searchSchema = json_decode($this->component->getSearchFieldSchema($this->gridField));

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
        $searchSchema = json_decode($this->component->getSearchFieldSchema($this->gridField));

        $this->assertEquals('field/testfield/schema/SearchForm', $searchSchema->formSchemaUrl);
        $this->assertEquals('Name', $searchSchema->name);
        $this->assertEquals('Search "Teams"', $searchSchema->placeholder);
        $this->assertEquals('test', $searchSchema->filters->Search__Name);
        $this->assertEquals('place', $searchSchema->filters->Search__City);
        $this->assertEquals('testfield', $searchSchema->gridfield);
    }
}
