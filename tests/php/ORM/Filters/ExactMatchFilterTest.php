<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Tests\Filters\ExactMatchFilterTest\Task;
use SilverStripe\ORM\Tests\Filters\ExactMatchFilterTest\Project;
use SilverStripe\ORM\DataList;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\Filters\SearchFilter;
use PHPUnit\Framework\Attributes\DataProvider;

class ExactMatchFilterTest extends SapphireTest
{
    protected static $fixture_file = 'ExactMatchFilterTest.yml';

    protected static $extra_dataobjects = [
        Task::class,
        Project::class,
    ];

    #[DataProvider('provideUsePlaceholders')]
    public function testUsePlaceholders(?bool $expectedID, ?bool $expectedTitle, bool $config, callable $fn): void
    {
        Config::modify()->set(DataList::class, 'use_placeholders_for_integer_ids', $config);
        [$idQueryUsesPlaceholders, $titleQueryUsesPlaceholders] = $this->usesPlaceholders($fn);
        $this->assertSame($expectedID, $idQueryUsesPlaceholders);
        $this->assertSame($expectedTitle, $titleQueryUsesPlaceholders);
    }

    public static function provideUsePlaceholders(): array
    {
        $ids = [1, 2, 3];
        $taskTitles = array_map(fn($i) => "Task $i", $ids);
        return [
            'primary key' => [
                'expectedID' => false,
                'expectedTitle' => null,
                'config' => false,
                'fn' => fn() => Task::get()->byIDs($ids)
            ],
            'primary key on relation' => [
                'expectedID' => false,
                'expectedTitle' => null,
                'config' => false,
                'fn' => fn() => Project::get()->filter('Tasks.ID', $ids)
            ],
            'foriegn key' => [
                'expectedID' => false,
                'expectedTitle' => null,
                'config' => false,
                'fn' => fn() => Task::get()->filter(['ProjectID' => $ids])
            ],
            'regular column' => [
                'expectedID' => null,
                'expectedTitle' => true,
                'config' => false,
                'fn' => fn() => Task::get()->filter(['Title' => $taskTitles])
            ],
            'primary key + regular column' => [
                'expectedID' => false,
                'expectedTitle' => true,
                'config' => false,
                'fn' => fn() => Task::get()->filter([
                    'ID' => $ids,
                    'Title' => $taskTitles
                ])
            ],
            'primary key config enabled' => [
                'expectedID' => true,
                'expectedTitle' => null,
                'config' => true,
                'fn' => fn() => Task::get()->byIDs($ids)
            ],
            'non int values' => [
                'expectedID' => true,
                'expectedTitle' => null,
                'config' => false,
                'fn' => fn() => Task::get()->filter(['ID' => ['a', 'b', 'c']])
            ],
        ];
    }

    private function usesPlaceholders(callable $fn): array
    {
        // force showqueries on to view executed SQL via output-buffering
        $list = $fn();
        $sql = $list->dataQuery()->sql();
        preg_match('#ID" IN \(([^\)]+)\)\)#', $sql, $matches);
        $idQueryUsesPlaceholders = isset($matches[1]) ? $matches[1] === '?, ?, ?' : null;
        preg_match('#"Title" IN \(([^\)]+)\)\)#', $sql, $matches);
        $titleQueryUsesPlaceholders = isset($matches[1]) ? $matches[1] === '?, ?, ?' : null;
        return [$idQueryUsesPlaceholders, $titleQueryUsesPlaceholders];
    }

    public static function provideMatches()
    {
        $scenarios = [
            // without modifiers
            [
                'filterValue' => null,
                'matchValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => null,
                'matchValue' => '',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => '',
                'matchValue' => null,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => '',
                'matchValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => false,
                'matchValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => true,
                'matchValue' => '',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => '',
                'matchValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => '',
                'matchValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => false,
                'matchValue' => null,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => null,
                'matchValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => true,
                'matchValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => false,
                'matchValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => true,
                'matchValue' => true,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => 'SomeValue',
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => 'somevalue',
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => null,
            ],
            [
                'filterValue' => 'SomeValue',
                'matchValue' => 'Some',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 1,
                'matchValue' => '1',
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => 1,
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => true,
            ],
            // unicode matches
            [
                'filterValue' => 'tohutō',
                'matchValue' => 'tohuto',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 'tohuto',
                'matchValue' => 'tohutō',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 'tohutō',
                'matchValue' => 'tohutō',
                'modifiers' => [],
                'matches' => true,
            ],
            // Some multi-value tests
            [
                'filterValue' => [123, 'somevalue', 'abc'],
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => null,
            ],
            [
                'filterValue' => [123, 'SomeValue', 'abc'],
                'matchValue' => 'Some',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => [1, 2, 3],
                'matchValue' => '1',
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => [4, 5, 6],
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => false,
            ],
            // test something that is clearly not strings, since exact match
            // is the default for ArrayList filtering which can have basically
            // anything as its value
            [
                'filterValue' => new ArrayData(['SomeField' => 'some value']),
                'matchValue' => new ArrayData(['SomeField' => 'some value']),
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => new ArrayData(['SomeField' => 'SoMe VaLuE']),
                'matchValue' => new ArrayData(['SomeField' => 'some value']),
                'modifiers' => [],
                'matches' => false,
            ],
            // case insensitive
            [
                'filterValue' => 'somevalue',
                'matchValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            // doesn't do partial matching even when case insensitive
            [
                'filterValue' => 'some',
                'matchValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => false,
            ],
        ];
        // negated
        foreach ($scenarios as $scenario) {
            $scenario['modifiers'][] = 'not';
            $scenario['matches'] = $scenario['matches'] === null ? null : !$scenario['matches'];
            $scenarios[] = $scenario;
        }
        // explicitly case sensitive
        foreach ($scenarios as $scenario) {
            if (!in_array('nocase', $scenario['modifiers'])) {
                $scenario['modifiers'][] = 'case';
                $scenarios[] = $scenario;
            }
        }
        return $scenarios;
    }

    #[DataProvider('provideMatches')]
    public function testMatches(mixed $filterValue, mixed $matchValue, array $modifiers, ?bool $matches)
    {
        // Test with explicit default case sensitivity rather than relying on the collation, so that database
        // settings don't interfere with the test
        foreach ([true, false] as $caseSensitive) {
            // Handle cases where the expected value can depend on the default case sensitivity
            if ($matches === null) {
                $nullMatch = !(in_array('case', $modifiers) ?: $caseSensitive);
                if (in_array('not', $modifiers)) {
                    $nullMatch = !$nullMatch;
                }
            }

            SearchFilter::config()->set('default_case_sensitive', $caseSensitive);
            $filter = new ExactMatchFilter();
            $filter->setValue($filterValue);
            $filter->setModifiers($modifiers);
            $this->assertSame($matches ?? $nullMatch, $filter->matches($matchValue));
        }
    }
}
