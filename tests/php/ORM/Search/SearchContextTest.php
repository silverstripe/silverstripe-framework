<?php

namespace SilverStripe\ORM\Tests\Search;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\ORM\Search\SearchContext;

class SearchContextTest extends SapphireTest
{

    protected static $fixture_file = 'SearchContextTest.yml';

    protected static $extra_dataobjects = array(
        SearchContextTest\Person::class,
        SearchContextTest\Book::class,
        SearchContextTest\Company::class,
        SearchContextTest\Project::class,
        SearchContextTest\Deadline::class,
        SearchContextTest\Action::class,
        SearchContextTest\AllFilterTypes::class,
    );

    public function testResultSetFilterReturnsExpectedCount()
    {
        $person = SearchContextTest\Person::singleton();
        $context = $person->getDefaultSearchContext();
        $results = $context->getResults(array('Name' => ''));
        $this->assertEquals(5, $results->Count());

        $results = $context->getResults(array('EyeColor' => 'green'));
        $this->assertEquals(2, $results->Count());

        $results = $context->getResults(array('EyeColor' => 'green', 'HairColor' => 'black'));
        $this->assertEquals(1, $results->Count());
    }

    public function testSummaryIncludesDefaultFieldsIfNotDefined()
    {
        $person = SearchContextTest\Person::singleton();
        $this->assertContains('Name', $person->summaryFields());

        $book = SearchContextTest\Book::singleton();
        $this->assertContains('Title', $book->summaryFields());
    }

    public function testAccessDefinedSummaryFields()
    {
        $company = SearchContextTest\Company::singleton();
        $this->assertContains('Industry', $company->summaryFields());
    }

    public function testPartialMatchUsedByDefaultWhenNotExplicitlySet()
    {
        $person = SearchContextTest\Person::singleton();
        $context = $person->getDefaultSearchContext();

        $this->assertEquals(
            array(
                "Name" => new PartialMatchFilter("Name"),
                "HairColor" => new PartialMatchFilter("HairColor"),
                "EyeColor" => new PartialMatchFilter("EyeColor")
            ),
            $context->getFilters()
        );
    }

    public function testDefaultFiltersDefinedWhenNotSetInDataObject()
    {
        $book = SearchContextTest\Book::singleton();
        $context = $book->getDefaultSearchContext();

        $this->assertEquals(
            array(
                "Title" => new PartialMatchFilter("Title")
            ),
            $context->getFilters()
        );
    }

    public function testUserDefinedFiltersAppearInSearchContext()
    {
        $company = SearchContextTest\Company::singleton();
        $context = $company->getDefaultSearchContext();

        $this->assertEquals(
            array(
                "Name" => new PartialMatchFilter("Name"),
                "Industry" => new PartialMatchFilter("Industry"),
                "AnnualProfit" => new PartialMatchFilter("AnnualProfit")
            ),
            $context->getFilters()
        );
    }

    public function testUserDefinedFieldsAppearInSearchContext()
    {
        $company = SearchContextTest\Company::singleton();
        $context = $company->getDefaultSearchContext();
        $this->assertEquals(
            new FieldList(
                (new TextField("Name", 'Name'))
                    ->setMaxLength(255),
                new TextareaField("Industry", 'Industry'),
                new NumericField("AnnualProfit", 'The Almighty Annual Profit')
            ),
            $context->getFields()
        );
    }

    public function testRelationshipObjectsLinkedInSearch()
    {
        $action3 = $this->objFromFixture(SearchContextTest\Action::class, 'action3');

        $project = SearchContextTest\Project::singleton();
        $context = $project->getDefaultSearchContext();

        $params = array("Name" => "Blog Website", "Actions__SolutionArea" => "technical");

        /** @var DataList $results */
        $results = $context->getResults($params);

        $this->assertEquals(1, $results->count());

        /** @var SearchContextTest\Project $project */
        $project = $results->first();

        $this->assertInstanceOf(SearchContextTest\Project::class, $project);
        $this->assertEquals("Blog Website", $project->Name);
        $this->assertEquals(2, $project->Actions()->Count());

        $this->assertEquals(
            "Get RSS feeds working",
            $project->Actions()->find('ID', $action3->ID)->Description
        );
    }

    public function testCanGenerateQueryUsingAllFilterTypes()
    {
        $all = SearchContextTest\AllFilterTypes::singleton();
        $context = $all->getDefaultSearchContext();
        $params = array(
            "ExactMatch" => "Match me exactly",
            "PartialMatch" => "partially",
            "CollectionMatch" => array(
                "ExistingCollectionValue",
                "NonExistingCollectionValue",
                4,
                "Inline'Quotes'"
            ),
            "StartsWith" => "12345",
            "EndsWith" => "ijkl",
            "Fulltext" => "two"
        );

        $results = $context->getResults($params);
        $this->assertEquals(1, $results->Count());
        $this->assertEquals("Filtered value", $results->First()->HiddenValue);
    }

    public function testStartsWithFilterCaseInsensitive()
    {
        $all = SearchContextTest\AllFilterTypes::singleton();
        $context = $all->getDefaultSearchContext();
        $params = array(
            "StartsWith" => "12345-6789 camelcase", // spelled lowercase
        );

        $results = $context->getResults($params);
        $this->assertEquals(1, $results->Count());
        $this->assertEquals("Filtered value", $results->First()->HiddenValue);
    }

    public function testEndsWithFilterCaseInsensitive()
    {
        $all = SearchContextTest\AllFilterTypes::singleton();
        $context = $all->getDefaultSearchContext();
        $params = array(
            "EndsWith" => "IJKL", // spelled uppercase
        );

        $results = $context->getResults($params);
        $this->assertEquals(1, $results->Count());
        $this->assertEquals("Filtered value", $results->First()->HiddenValue);
    }

    public function testSearchContextSummary()
    {
        $filters = [
            'KeywordSearch' => PartialMatchFilter::create('KeywordSearch'),
            'Country' => PartialMatchFilter::create('Country'),
            'CategoryID' => PartialMatchFilter::create('CategoryID'),
            'Featured' => PartialMatchFilter::create('Featured'),
            'Nothing' => PartialMatchFilter::create('Nothing'),
        ];

        $fields = FieldList::create(
            TextField::create('KeywordSearch', 'Keywords'),
            TextField::create('Country', 'Country'),
            DropdownField::create('CategoryID', 'Category', [
                1 => 'Category one',
                2 => 'Category two',
            ]),
            CheckboxField::create('Featured', 'Featured')
        );

        $context = SearchContext::create(
            SearchContextTest\Person::class,
            $fields,
            $filters
        );

        $context->setSearchParams([
            'KeywordSearch' => 'tester',
            'Country' => null,
            'CategoryID' => 2,
            'Featured' => 1,
            'Nothing' => 'empty',
        ]);

        $list = $context->getSummary();

        $this->assertEquals(3, $list->count());
        // KeywordSearch should be in the summary
        $keyword = $list->find('Field', 'Keywords');
        $this->assertNotNull($keyword);
        $this->assertEquals('tester', $keyword->Value);

        // Country should be skipped over
        $country = $list->find('Field', 'Country');
        $this->assertNull($country);

        // Category should be expressed as the label
        $category = $list->find('Field', 'Category');
        $this->assertNotNull($category);
        $this->assertEquals('Category two', $category->Value);

        // Featured should have no value, since it's binary
        $featured = $list->find('Field', 'Featured');
        $this->assertNotNull($featured);
        $this->assertNull($featured->Value);

        // "Nothing" should come back null since there's no field for it
        $nothing = $list->find('Field', 'Nothing');
        $this->assertNull($nothing);
    }
}
