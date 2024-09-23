<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\Filters\SearchFilter;
use PHPUnit\Framework\Attributes\DataProvider;

class PartialMatchFilterTest extends SapphireTest
{

    public static function provideMatches()
    {
        $scenarios = [
            // without modifiers
            'null partially matches null' => [
                'filterValue' => null,
                'matchValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'null partially matches empty' => [
                'filterValue' => null,
                'matchValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'empty partially matches null' => [
                'filterValue' => '',
                'matchValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'empty partially matches empty' => [
                'filterValue' => '',
                'matchValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'false partially matches empty' => [
                'filterValue' => false,
                'matchValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'true doesnt partially match empty' => [
                'filterValue' => true,
                'matchValue' => '',
                'modifiers' => [],
                'matches' => false,
            ],
            'empty partially matches false' => [
                'filterValue' => '',
                'matchValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            'empty doesnt partially match true' => [
                'filterValue' => '',
                'matchValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            'false partially matches null' => [
                'filterValue' => false,
                'matchValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'null partially matches false' => [
                'filterValue' => null,
                'matchValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            'true doesnt partially match false' => [
                'filterValue' => true,
                'matchValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            'false doesnt partially match true' => [
                'filterValue' => false,
                'matchValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            'false partially matches false' => [
                'filterValue' => false,
                'matchValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            'true partially matches true' => [
                'filterValue' => true,
                'matchValue' => true,
                'modifiers' => [],
                'matches' => true,
            ],
            'number is cast to string' => [
                'filterValue' => 1,
                'matchValue' => '1',
                'modifiers' => [],
                'matches' => true,
            ],
            'numeric match' => [
                'filterValue' => 1,
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => true,
            ],
            'partial numeric match' => [
                'filterValue' => '1',
                'matchValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            'partial numeric match2' => [
                'filterValue' => 1,
                'matchValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            'partial numeric match3' => [
                'filterValue' => 0,
                'matchValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            'case sensitive match' => [
                'filterValue' => 'SomeValue',
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            'case sensitive mismatch' => [
                'filterValue' => 'somevalue',
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => null,
            ],
            'case sensitive partial match' => [
                'filterValue' => 'meVal',
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            'case sensitive partial mismatch' => [
                'filterValue' => 'meval',
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => null,
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
                'filterValue' => [123, 'meVal', 'abc'],
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => [123, 'meval', 'abc'],
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => null,
            ],
            [
                'filterValue' => [4, 5, 6],
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => false,
            ],
            // These will both evaluate to true because the __toString() method just returns the class name.
            // We're testing this scenario because ArrayList might contain arbitrary values
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
                'matches' => true,
            ],
            // case insensitive
            [
                'filterValue' => 'somevalue',
                'matchValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => 'some',
                'matchValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => 'meval',
                'matchValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => 'different',
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
        // explicit case sensitive
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
            $filter = new PartialMatchFilter();
            $filter->setValue($filterValue);
            $filter->setModifiers($modifiers);
            $this->assertSame($matches ?? $nullMatch, $filter->matches($matchValue));
        }
    }
}
