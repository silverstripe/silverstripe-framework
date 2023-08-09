<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\StartsWithFilter;
use SilverStripe\View\ArrayData;

class StartsWithFilterTest extends SapphireTest
{

    public function provideMatches()
    {
        $scenarios = [
            // without modifiers
            'null starts with null' => [
                'filterValue' => null,
                'objValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'empty starts with null' => [
                'filterValue' => null,
                'objValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'null starts with empty' => [
                'filterValue' => '',
                'objValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'empty starts with empty' => [
                'filterValue' => '',
                'objValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'empty starts with false' => [
                'filterValue' => false,
                'objValue' => '',
                'modifiers' => [],
                'matches' => true,
            ],
            'true doesnt start with empty' => [
                'filterValue' => true,
                'objValue' => '',
                'modifiers' => [],
                'matches' => false,
            ],
            'false doesnt start with empty' => [
                'filterValue' => '',
                'objValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            'true doesnt start with empty' => [
                'filterValue' => '',
                'objValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            'null starts with false' => [
                'filterValue' => false,
                'objValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            'false doesnt start with null' => [
                'filterValue' => null,
                'objValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            'false doesnt start with true' => [
                'filterValue' => true,
                'objValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            'true doesnt start with false' => [
                'filterValue' => false,
                'objValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            'false doesnt start with false' => [
                'filterValue' => false,
                'objValue' => false,
                'modifiers' => [],
                'matches' => false,
            ],
            'true doesnt start with true' => [
                'filterValue' => true,
                'objValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            'number is cast to string' => [
                'filterValue' => 1,
                'objValue' => '1',
                'modifiers' => [],
                'matches' => true,
            ],
            '1 starts with 1' => [
                'filterValue' => 1,
                'objValue' => 1,
                'modifiers' => [],
                'matches' => true,
            ],
            '100 starts with 1' => [
                'filterValue' => '1',
                'objValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            '100 still starts with 1' => [
                'filterValue' => 1,
                'objValue' => 100,
                'modifiers' => [],
                'matches' => true,
            ],
            '100 doesnt start with 0' => [
                'filterValue' => 0,
                'objValue' => 100,
                'modifiers' => [],
                'matches' => false,
            ],
            'SomeValue starts with SomeValue' => [
                'filterValue' => 'SomeValue',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            'SomeValue doesnt start with SomeValue' => [
                'filterValue' => 'somevalue',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => false,
            ],
            'SomeValue doesnt start with meVal' => [
                'filterValue' => 'meVal',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => false,
            ],
            'SomeValue starts with Some' => [
                'filterValue' => 'Some',
                'objValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => true,
            ],
            'SomeValue doesnt with sOmE' => [
                'filterValue' => 'sOmE',
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
                'filterValue' => 'sOmE',
                'objValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => true,
            ],
            [
                'filterValue' => 'meval',
                'objValue' => 'SomeValue',
                'modifiers' => ['nocase'],
                'matches' => false,
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
    public function testMatches(mixed $filterValue, mixed $matchValue, array $modifiers, bool $matches)
    {
        $filter = new StartsWithFilter();
        $filter->setValue($filterValue);
        $filter->setModifiers($modifiers);
        $this->assertSame($matches, $filter->matches($matchValue));
    }
}
