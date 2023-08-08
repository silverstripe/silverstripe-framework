<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Tests\Filters\ExactMatchFilterTest\Task;
use SilverStripe\ORM\Tests\Filters\ExactMatchFilterTest\Project;
use SilverStripe\ORM\DataList;

class ExactMatchFilterTest extends SapphireTest
{
    protected static $fixture_file = 'ExactMatchFilterTest.yml';

    protected static $extra_dataobjects = [
        Task::class,
        Project::class,
    ];

    /**
     * @dataProvider provideUsePlaceholders
     */
    public function testUsePlaceholders(?bool $expectedID, ?bool $expectedTitle, bool $config, callable $fn): void
    {
        Config::modify()->set(DataList::class, 'use_placeholders_for_integer_ids', $config);
        [$idQueryUsesPlaceholders, $titleQueryUsesPlaceholders] = $this->usesPlaceholders($fn);
        $this->assertSame($expectedID, $idQueryUsesPlaceholders);
        $this->assertSame($expectedTitle, $titleQueryUsesPlaceholders);
    }

    public function provideUsePlaceholders(): array
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
}
