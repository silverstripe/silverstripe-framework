<?php

namespace SilverStripe\ORM\Tests\Search;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\Filters\PartialMatchFilter;

class SearchContextTest extends SapphireTest {

	protected static $fixture_file = 'SearchContextTest.yml';

	protected $extraDataObjects = array(
		SearchContextTest\Person::class,
		SearchContextTest\Book::class,
		SearchContextTest\Company::class,
		SearchContextTest\Project::class,
		SearchContextTest\Deadline::class,
		SearchContextTest\Action::class,
		SearchContextTest\AllFilterTypes::class,
	);

	public function testResultSetFilterReturnsExpectedCount() {
		$person = SearchContextTest\Person::singleton();
		$context = $person->getDefaultSearchContext();
		$results = $context->getResults(array('Name'=>''));
		$this->assertEquals(5, $results->Count());

		$results = $context->getResults(array('EyeColor'=>'green'));
		$this->assertEquals(2, $results->Count());

		$results = $context->getResults(array('EyeColor'=>'green', 'HairColor'=>'black'));
		$this->assertEquals(1, $results->Count());
	}

	public function testSummaryIncludesDefaultFieldsIfNotDefined() {
		$person = SearchContextTest\Person::singleton();
		$this->assertContains('Name', $person->summaryFields());

		$book = SearchContextTest\Book::singleton();
		$this->assertContains('Title', $book->summaryFields());
	}

	public function testAccessDefinedSummaryFields() {
		$company = SearchContextTest\Company::singleton();
		$this->assertContains('Industry', $company->summaryFields());
	}

	public function testPartialMatchUsedByDefaultWhenNotExplicitlySet() {
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

	public function testDefaultFiltersDefinedWhenNotSetInDataObject() {
		$book = SearchContextTest\Book::singleton();
		$context = $book->getDefaultSearchContext();

		$this->assertEquals(
			array(
				"Title" => new PartialMatchFilter("Title")
			),
			$context->getFilters()
		);
	}

	public function testUserDefinedFiltersAppearInSearchContext() {
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

	public function testUserDefinedFieldsAppearInSearchContext() {
		$company = SearchContextTest\Company::singleton();
		$context = $company->getDefaultSearchContext();
		$fields = $context->getFields();
		$this->assertEquals(
			new FieldList(
				new TextField("Name", 'Name'),
				new TextareaField("Industry", 'Industry'),
				new NumericField("AnnualProfit", 'The Almighty Annual Profit')
			),
			$context->getFields()
		);
	}

	public function testRelationshipObjectsLinkedInSearch() {
		$action3 = $this->objFromFixture(SearchContextTest\Action::class, 'action3');

		$project = singleton(SearchContextTest\Project::class);
		$context = $project->getDefaultSearchContext();

		$params = array("Name"=>"Blog Website", "Actions__SolutionArea"=>"technical");

		$results = $context->getResults($params);

		$this->assertEquals(1, $results->Count());

		$project = $results->First();

		$this->assertInstanceOf(SearchContextTest\Project::class, $project);
		$this->assertEquals("Blog Website", $project->Name);
		$this->assertEquals(2, $project->Actions()->Count());

		$this->assertEquals(
			"Get RSS feeds working",
			$project->Actions()->find('ID', $action3->ID)->Description
		);
	}

	public function testCanGenerateQueryUsingAllFilterTypes() {
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

	public function testStartsWithFilterCaseInsensitive() {
		$all = SearchContextTest\AllFilterTypes::singleton();
		$context = $all->getDefaultSearchContext();
		$params = array(
			"StartsWith" => "12345-6789 camelcase", // spelled lowercase
		);

		$results = $context->getResults($params);
		$this->assertEquals(1, $results->Count());
		$this->assertEquals("Filtered value", $results->First()->HiddenValue);
	}

	public function testEndsWithFilterCaseInsensitive() {
		$all = SearchContextTest\AllFilterTypes::singleton();
		$context = $all->getDefaultSearchContext();
		$params = array(
			"EndsWith" => "IJKL", // spelled uppercase
		);

		$results = $context->getResults($params);
		$this->assertEquals(1, $results->Count());
		$this->assertEquals("Filtered value", $results->First()->HiddenValue);
	}
}


