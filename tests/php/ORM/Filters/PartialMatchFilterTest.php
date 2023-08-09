<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\View\ArrayData;

class PartialMatchFilterTest extends SapphireTest
{

    public function provideMatches()
    {
        $scenarios = [
            // without modifiers
            'null partially matches null' => [
                'filterValue' => null,
                'objValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'null partially matches empty' => [
                'filterValue' => null,
                'objValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'empty partially matches null' => [
                'filterValue' => '',
                'objValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'empty partially matches empty' => [
                'filterValue' => '',
                'objValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'false partially matches empty' => [
                'filterValue' => false,
                'objValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'true doesnt partially match empty' => [
                'filterValue' => true,
                'objValue' => '',
                'modifiers' => [],
                'matches' => false,
            ],
            'empty partially matches false' => [
                'filterValue' => '',
                'objValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            'empty doesnt partially match true' => [
                'filterValue' => '',
                'objValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            'false partially matches null' => [
                'filterValue' => false,
                'objValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'null partially matches false' => [
                'filterValue' => null,
                'objValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            'true doesnt partially match false' => [
                'filterValue' => true,
                'objValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            'false doesnt partially match true' => [
                'filterValue' => false,
                'objValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            'false partially matches false' => [
                'filterValue' => false,
                'objValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            'true partially matches true' => [
                'filterValue' => true,
                'objValue' => true,
                'modifiers' => [],
                'matches' => true,
            ],
            'number is cast to string' => [
                'filterValue' => 1,
                'objValue' => '1',
                'modifiers' => [],
                'matches' => true,
            ],
            'numeric match' => [
                'filterValue' => 1,
                'objValue' => 1,
                'modifiers' => [],
                'matches' => true,
            ],
            'partial numeric match' => [
                'filterValue' => '1',
                'objValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            'partial numeric match2' => [
                'filterValue' => 1,
                'objValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            'partial numeric match3' => [
                'filterValue' => 0,
                'objValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            'case sensitive match' => [
                'filterValue' => 'SomeValue',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            'case sensitive mismatch' => [
                'filterValue' => 'somevalue',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => false,
            ],
            'case sensitive partial match' => [
                'filterValue' => 'meVal',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            'case sensitive partial mismatch' => [
                'filterValue' => 'meval',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => false,
            ],
            // These will both evaluate to true because the __toString() method just returns the class name.
            [
                'filterValue' => new ArrayData(['SomeField' => 'some value']),
                'objValue' => new ArrayData(['SomeField' => 'some value']),
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => new ArrayData(['SomeField' => 'SoMe VaLuE']),
                'objValue' => new ArrayData(['SomeField' => 'some value']),
                'modifiers' => [],
                'matches' => true,
            ],
            // case insensitive
            [
                'filterValue' => 'somevalue',
                'objValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => 'some',
                'objValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => 'meval',
                'objValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => 'different',
                'objValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => false,
            ],
            // These will both evaluate to true because the __toString() method just returns the class name.
            [
                'filterValue' => new ArrayData(['SomeField' => 'SoMe VaLuE']),
                'objValue' => new ArrayData(['SomeField' => 'some value']),
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => new ArrayData(['SomeField' => 'VaLuE']),
                'objValue' => new ArrayData(['SomeField' => 'some value']),
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
        ];
        // negated
        foreach ($scenarios as $scenario) {
            $scenario['modifiers'][] = 'not';
            $scenario['matches'] = !$scenario['matches'];
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

    /**
     * @dataProvider provideMatches
     */
    public function testMatches(mixed $filterValue, mixed $objValue, array $modifiers, bool $matches)
    {
        $filter = new PartialMatchFilter();
        $filter->setValue($filterValue);
        $filter->setModifiers($modifiers);
        $this->assertSame($matches, $filter->matches($objValue));
    }
}
