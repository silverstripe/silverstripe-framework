<?php

namespace SilverStripe\ORM\Tests\Search;

use ReflectionMethod;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Filters\StartsWithFilter;
use SilverStripe\ORM\Search\BasicSearchContext;
use SilverStripe\Model\ArrayData;
use PHPUnit\Framework\Attributes\DataProvider;

class BasicSearchContextTest extends SapphireTest
{
    protected static $fixture_file = 'BasicSearchContextTest.yml';

    protected static $extra_dataobjects = [
        SearchContextTest\GeneralSearch::class,
    ];

    private function getList(): ArrayList
    {
        $data = [
            [
                'Name' => 'James',
                'Email' => 'james@example.com',
                'HairColor' => 'brown',
                'EyeColor' => 'brown',
            ],
            [
                'Name' => 'John',
                'Email' => 'john@example.com',
                'HairColor' => 'blond',
                'EyeColor' => 'blue',
            ],
            [
                'Name' => 'Jane',
                'Email' => 'jane@example.com',
                'HairColor' => 'brown',
                'EyeColor' => 'green',
            ],
            [
                'Name' => 'Hemi',
                'Email' => 'hemi@example.com',
                'HairColor' => 'black',
                'EyeColor' => 'brown eyes',
            ],
            [
                'Name' => 'Sara',
                'Email' => 'sara@example.com',
                'HairColor' => 'black',
                'EyeColor' => 'green',
            ],
            [
                'Name' => 'MatchNothing',
                'Email' => 'MatchNothing',
                'HairColor' => 'MatchNothing',
                'EyeColor' => 'MatchNothing',
            ],
        ];

        $list = new ArrayList();
        foreach ($data as $datum) {
            $list->add(new ArrayData($datum));
        }
        return $list;
    }

    private function getSearchableFields(string $generalField): FieldList
    {
        return new FieldList([
            new HiddenField($generalField),
            new TextField('Name'),
            new TextField('Email'),
            new TextField('HairColor'),
            new TextField('EyeColor'),
        ]);
    }

    public function testResultSetFilterReturnsExpectedCount()
    {
        $context = new BasicSearchContext(ArrayData::class);
        $results = $context->getQuery(['Name' => ''], existingQuery: $this->getList());

        $this->assertEquals(6, $results->Count());

        $results = $context->getQuery(['EyeColor' => 'green'], existingQuery: $this->getList());
        $this->assertEquals(2, $results->Count());

        $results = $context->getQuery(['EyeColor' => 'green', 'HairColor' => 'black'], existingQuery: $this->getList());
        $this->assertEquals(1, $results->Count());
    }

    public static function provideApplySearchFilters()
    {
        $idFilter = new ExactMatchFilter('ID');
        $idFilter->setModifiers(['nocase']);
        return [
            'defaults to PartialMatch' => [
                'searchParams' => [
                    'q' => 'This one gets ignored',
                    'ID' => 47,
                    'Name' => 'some search term',
                ],
                'filters' => null,
                'expected' => [
                    'q' => 'This one gets ignored',
                    'ID:PartialMatch' => 47,
                    'Name:PartialMatch' => 'some search term',
                ],
            ],
            'respects custom filters and modifiers' => [
                'searchParams' => [
                    'q' => 'This one gets ignored',
                    'ID' => 47,
                    'Name' => 'some search term',
                ],
                'filters' => ['ID' => $idFilter],
                'expected' => [
                    'q' => 'This one gets ignored',
                    'ID:ExactMatch:nocase' => 47,
                    'Name:PartialMatch' => 'some search term',
                ],
            ],
        ];
    }

    #[DataProvider('provideApplySearchFilters')]
    public function testApplySearchFilters(array $searchParams, ?array $filters, array $expected)
    {
        $context = new BasicSearchContext(ArrayData::class);
        $reflectionApplySearchFilters = new ReflectionMethod($context, 'applySearchFilters');
        $reflectionApplySearchFilters->setAccessible(true);

        if ($filters) {
            $context->setFilters($filters);
        }

        $this->assertSame($expected, $reflectionApplySearchFilters->invoke($context, $searchParams));
    }

    public static function provideGetGeneralSearchFilterTerm()
    {
        return [
            'defaults to case-insensitive partial match' => [
                'filterType' => null,
                'fieldFilter' => null,
                'expected' => 'PartialMatch:nocase',
            ],
            'uses default even when config is explicitly "null"' => [
                'filterType' => null,
                'fieldFilter' => new StartsWithFilter('MyField'),
                'expected' => 'PartialMatch:nocase',
            ],
            'uses configuration filter over field-specific filter' => [
                'filterType' => ExactMatchFilter::class,
                'fieldFilter' => new StartsWithFilter(),
                'expected' => 'ExactMatch',
            ],
            'uses field-specific filter if provided and config is empty string' => [
                'filterType' => '',
                'fieldFilter' => new StartsWithFilter('MyField'),
                'expected' => 'StartsWith',
            ],
        ];
    }

    #[DataProvider('provideGetGeneralSearchFilterTerm')]
    public function testGetGeneralSearchFilterTerm(?string $filterType, ?SearchFilter $fieldFilter, string $expected)
    {
        $context = new BasicSearchContext(ArrayData::class);
        $reflectionGetGeneralSearchFilterTerm = new ReflectionMethod($context, 'getGeneralSearchFilterTerm');
        $reflectionGetGeneralSearchFilterTerm->setAccessible(true);

        if ($fieldFilter) {
            $context->setFilters(['MyField' => $fieldFilter]);
        }

        Config::modify()->set(ArrayData::class, 'general_search_field_filter', $filterType);

        $this->assertSame($expected, $reflectionGetGeneralSearchFilterTerm->invoke($context, 'MyField'));
    }

    public static function provideGetQuery()
    {
        // Note that the search TERM is the same for both scenarios,
        // but because the search FIELD is different, we get different results.
        return [
            'search against hair' => [
                'searchParams' => [
                    'HairColor' => 'brown',
                ],
                'expected' => [
                    'James',
                    'Jane',
                ],
            ],
            'search against eyes' => [
                'searchParams' => [
                    'EyeColor' => 'brown',
                ],
                'expected' => [
                    'James',
                    'Hemi',
                ],
            ],
            'search against all' => [
                'searchParams' => [
                    'q' => 'brown',
                ],
                'expected' => [
                    'James',
                    'Jane',
                    'Hemi',
                ],
            ],
        ];
    }

    #[DataProvider('provideGetQuery')]
    public function testGetQuery(array $searchParams, array $expected)
    {
        $list = $this->getList();
        $context = new BasicSearchContext(ArrayData::class);
        $context->setFields($this->getSearchableFields(BasicSearchContext::config()->get('general_search_field_name')));

        $results = $context->getQuery($searchParams, existingQuery: $list);
        $this->assertSame($expected, $results->column('Name'));
    }

    public static function provideGetQueryLimit(): array
    {
        return [
            'no limit' => [
                'limit' => null,
                'expected' => [
                    'James',
                    'John',
                    'Jane',
                    'Hemi',
                    'Sara',
                    'MatchNothing',
                ],
            ],
            'limit int' => [
                'limit' => 3,
                'expected' => [
                    'James',
                    'John',
                    'Jane',
                ],
            ],
            'paginated limit' => [
                'limit' => [
                    'limit' => 2,
                    'start' => 3,
                ],
                'expected' => [
                    'Hemi',
                    'Sara',
                ],
            ],
        ];
    }

    #[DataProvider('provideGetQueryLimit')]
    public function testGetQueryLimit(mixed $limit, array $expected): void
    {
        $context = new BasicSearchContext(ArrayData::class);
        $results = $context->getQuery([], limit: $limit, existingQuery: $this->getList());
        $this->assertSame($expected, $results->column('Name'));
    }

    public function testGeneralSearch()
    {
        $list = $this->getList();
        $generalField = BasicSearchContext::config()->get('general_search_field_name');
        $context = new BasicSearchContext(ArrayData::class);
        $context->setFields($this->getSearchableFields($generalField));

        $results = $context->getQuery([$generalField => 'brown'], existingQuery: $list);
        $this->assertSame(['James', 'Jane', 'Hemi'], $results->column('Name'));
        $results = $context->getQuery([$generalField => 'b'], existingQuery: $list);
        $this->assertSame(['James', 'John', 'Jane', 'Hemi', 'Sara'], $results->column('Name'));
    }

    public function testGeneralSearchSplitTerms()
    {
        $list = $this->getList();
        $generalField = BasicSearchContext::config()->get('general_search_field_name');
        $context = new BasicSearchContext(ArrayData::class);
        $context->setFields($this->getSearchableFields($generalField));

        // These terms don't exist in a single field in this order on any object, but they do exist in separate fields.
        $results = $context->getQuery([$generalField => 'john blue'], existingQuery: $list);
        $this->assertSame(['John'], $results->column('Name'));
        $results = $context->getQuery([$generalField => 'eyes sara'], existingQuery: $list);
        $this->assertSame(['Hemi', 'Sara'], $results->column('Name'));
    }

    public function testGeneralSearchNoSplitTerms()
    {
        Config::modify()->set(ArrayData::class, 'general_search_split_terms', false);
        $list = $this->getList();
        $generalField = BasicSearchContext::config()->get('general_search_field_name');
        $context = new BasicSearchContext(ArrayData::class);
        $context->setFields($this->getSearchableFields($generalField));

        // These terms don't exist in a single field in this order on any object
        $results = $context->getQuery([$generalField => 'john blue'], existingQuery: $list);
        $this->assertCount(0, $results);

        // These terms exist in a single field, but not in this order.
        $results = $context->getQuery([$generalField => 'eyes brown'], existingQuery: $list);
        $this->assertCount(0, $results);

        // These terms exist in a single field in this order.
        $results = $context->getQuery([$generalField => 'brown eyes'], existingQuery: $list);
        $this->assertSame(['Hemi'], $results->column('Name'));
    }

    public function testSpecificFieldsCanBeSkipped()
    {
        $general1 = $this->objFromFixture(SearchContextTest\GeneralSearch::class, 'general1');
        $list = new ArrayList();
        $list->merge(SearchContextTest\GeneralSearch::get());
        $generalField = BasicSearchContext::config()->get('general_search_field_name');
        $context = new BasicSearchContext(SearchContextTest\GeneralSearch::class);

        // We're searching for a value that DOES exist in a searchable field,
        // but that field is set to be skipped by general search.
        $results = $context->getQuery([$generalField => $general1->ExcludeThisField], existingQuery: $list);
        $this->assertNotEmpty($general1->ExcludeThisField);
        $this->assertCount(0, $results);
    }
}
