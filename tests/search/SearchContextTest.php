<?php

class SearchContextTest extends SapphireTest {

	protected static $fixture_file = 'SearchContextTest.yml';

	protected $extraDataObjects = [
		'SearchContextTest_Person',
		'SearchContextTest_Book',
		'SearchContextTest_Company',
		'SearchContextTest_Project',
		'SearchContextTest_Deadline',
		'SearchContextTest_Action',
		'SearchContextTest_AllFilterTypes',
	];

	public function testResultSetFilterReturnsExpectedCount() {
		$person = singleton('SearchContextTest_Person');
		$context = $person->getDefaultSearchContext();
		$results = $context->getResults(['Name'=>'']);
		$this->assertEquals(5, $results->Count());

		$results = $context->getResults(['EyeColor'=>'green']);
		$this->assertEquals(2, $results->Count());

		$results = $context->getResults(['EyeColor'=>'green', 'HairColor'=>'black']);
		$this->assertEquals(1, $results->Count());
	}

	public function testSummaryIncludesDefaultFieldsIfNotDefined() {
		$person = singleton('SearchContextTest_Person');
		$this->assertContains('Name', $person->summaryFields());

		$book = singleton('SearchContextTest_Book');
		$this->assertContains('Title', $book->summaryFields());
	}

	public function testAccessDefinedSummaryFields() {
		$company = singleton('SearchContextTest_Company');
		$this->assertContains('Industry', $company->summaryFields());
	}

	public function testPartialMatchUsedByDefaultWhenNotExplicitlySet() {
		$person = singleton('SearchContextTest_Person');
		$context = $person->getDefaultSearchContext();

		$this->assertEquals(
			[
				"Name" => new PartialMatchFilter("Name"),
				"HairColor" => new PartialMatchFilter("HairColor"),
				"EyeColor" => new PartialMatchFilter("EyeColor")
			],
			$context->getFilters()
		);
	}

	public function testDefaultFiltersDefinedWhenNotSetInDataObject() {
		$book = singleton('SearchContextTest_Book');
		$context = $book->getDefaultSearchContext();

		$this->assertEquals(
			[
				"Title" => new PartialMatchFilter("Title")
			],
			$context->getFilters()
		);
	}

	public function testUserDefinedFiltersAppearInSearchContext() {
		$company = singleton('SearchContextTest_Company');
		$context = $company->getDefaultSearchContext();

		$this->assertEquals(
			[
				"Name" => new PartialMatchFilter("Name"),
				"Industry" => new PartialMatchFilter("Industry"),
				"AnnualProfit" => new PartialMatchFilter("AnnualProfit")
			],
			$context->getFilters()
		);
	}

	public function testUserDefinedFieldsAppearInSearchContext() {
		$company = singleton('SearchContextTest_Company');
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
		$action3 = $this->objFromFixture('SearchContextTest_Action', 'action3');

		$project = singleton('SearchContextTest_Project');
		$context = $project->getDefaultSearchContext();

		$params = ["Name"=>"Blog Website", "Actions__SolutionArea"=>"technical"];

		$results = $context->getResults($params);

		$this->assertEquals(1, $results->Count());

		$project = $results->First();

		$this->assertInstanceOf('SearchContextTest_Project', $project);
		$this->assertEquals("Blog Website", $project->Name);
		$this->assertEquals(2, $project->Actions()->Count());

		$this->assertEquals(
			"Get RSS feeds working",
			$project->Actions()->find('ID', $action3->ID)->Description
		);
	}

	public function testCanGenerateQueryUsingAllFilterTypes() {
		$all = singleton("SearchContextTest_AllFilterTypes");
		$context = $all->getDefaultSearchContext();
		$params = [
			"ExactMatch" => "Match me exactly",
			"PartialMatch" => "partially",
			"CollectionMatch" => ["ExistingCollectionValue","NonExistingCollectionValue",4,"Inline'Quotes'"],
			"StartsWith" => "12345",
			"EndsWith" => "ijkl",
			"Fulltext" => "two"
		];

		$results = $context->getResults($params);
		$this->assertEquals(1, $results->Count());
		$this->assertEquals("Filtered value", $results->First()->HiddenValue);
	}

	public function testStartsWithFilterCaseInsensitive() {
		$all = singleton("SearchContextTest_AllFilterTypes");
		$context = $all->getDefaultSearchContext();
		$params = [
			"StartsWith" => "12345-6789 camelcase", // spelled lowercase
		];

		$results = $context->getResults($params);
		$this->assertEquals(1, $results->Count());
		$this->assertEquals("Filtered value", $results->First()->HiddenValue);
	}

	public function testEndsWithFilterCaseInsensitive() {
		$all = singleton("SearchContextTest_AllFilterTypes");
		$context = $all->getDefaultSearchContext();
		$params = [
			"EndsWith" => "IJKL", // spelled uppercase
		];

		$results = $context->getResults($params);
		$this->assertEquals(1, $results->Count());
		$this->assertEquals("Filtered value", $results->First()->HiddenValue);
	}



}

class SearchContextTest_Person extends DataObject implements TestOnly {

	private static $db = [
		"Name" => "Varchar",
		"Email" => "Varchar",
		"HairColor" => "Varchar",
		"EyeColor" => "Varchar"
	];

	private static $searchable_fields = [
		"Name", "HairColor", "EyeColor"
	];

}

class SearchContextTest_Book extends DataObject implements TestOnly {

	private static $db = [
		"Title" => "Varchar",
		"Summary" => "Varchar"
	];

}

class SearchContextTest_Company extends DataObject implements TestOnly {

	private static $db = [
		"Name" => "Varchar",
		"Industry" => "Varchar",
		"AnnualProfit" => "Int"
	];

	private static $summary_fields = [
		"Industry"
	];

	private static $searchable_fields = [
		"Name" => "PartialMatchFilter",
		"Industry" => [
			'field' => "TextareaField"
		],
		"AnnualProfit" => [
			'field' => "NumericField",
			'filter' => "PartialMatchFilter",
			'title' => 'The Almighty Annual Profit'
		]
	];

}

class SearchContextTest_Project extends DataObject implements TestOnly {

	private static $db = [
		"Name" => "Varchar"
	];

	private static $has_one = [
		"Deadline" => "SearchContextTest_Deadline"
	];

	private static $has_many = [
		"Actions" => "SearchContextTest_Action"
	];

	private static $searchable_fields = [
		"Name" => "PartialMatchFilter",
		"Actions.SolutionArea" => "ExactMatchFilter",
		"Actions.Description" => "PartialMatchFilter"
	];

}

class SearchContextTest_Deadline extends DataObject implements TestOnly {

	private static $db = [
		"CompletionDate" => "SS_Datetime"
	];

	private static $has_one = [
		"Project" => "SearchContextTest_Project"
	];

}

class SearchContextTest_Action extends DataObject implements TestOnly {

	private static $db = [
		"Description" => "Text",
		"SolutionArea" => "Varchar"
	];

	private static $has_one = [
		"Project" => "SearchContextTest_Project"
	];

}

class SearchContextTest_AllFilterTypes extends DataObject implements TestOnly {

	private static $db = [
		"ExactMatch" => "Varchar",
		"PartialMatch" => "Varchar",
		"SubstringMatch" => "Varchar",
		"CollectionMatch" => "Varchar",
		"StartsWith" => "Varchar",
		"EndsWith" => "Varchar",
		"HiddenValue" => "Varchar",
		'FulltextField' => 'Text',
	];

	private static $searchable_fields = [
		"ExactMatch" => "ExactMatchFilter",
		"PartialMatch" => "PartialMatchFilter",
		"CollectionMatch" => "ExactMatchFilter",
		"StartsWith" => "StartsWithFilter",
		"EndsWith" => "EndsWithFilter",
		"FulltextField" => "FulltextFilter",
	];

}


