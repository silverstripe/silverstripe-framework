<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\LessThanFilter;
use SilverStripe\View\ArrayData;

class LessThanFilterTest extends SapphireTest
{

    public function provideMatches()
    {
        $scenarios = [
            // without modifiers
            [
                'filterValue' => true,
                'matchValue' => null,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => false,
                'matchValue' => null,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => null,
                'matchValue' => true,
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
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => false,
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 1,
                'matchValue' => true,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 1,
                'matchValue' => false,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => null,
                'matchValue' => null,
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
                'filterValue' => null,
                'matchValue' => '',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => '',
                'matchValue' => '',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 'SomeValue',
                'matchValue' => 'SomeValue',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 'SomeValue',
                'matchValue' => 'somevalue',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => '1',
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 1,
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 2,
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => 1,
                'matchValue' => 2,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => '2',
                'matchValue' => 1,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => 2,
                'matchValue' => '1',
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => '1',
                'matchValue' => 2,
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => 1,
                'matchValue' => '2',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => '12',
                'matchValue' => 2,
                'modifiers' => [],
                'matches' => true,
            ],
            [
                'filterValue' => 12,
                'matchValue' => '2',
                'modifiers' => [],
                'matches' => true,
            ],
            // unicode matches - macrons are "greater" than their non-macron equivalent
            [
                'filterValue' => 'tohutō',
                'matchValue' => 'tohuto',
                'modifiers' => [],
                'matches' => true,
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
                'matches' => false,
            ],
            // Some multi-value tests
            [
                'filterValue' => [123, '99', '50'],
                'matchValue' => '200',
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => [123, '250', '50'],
                'matchValue' => 200,
                'modifiers' => [],
                'matches' => true,
            ],
            // We're testing this scenario because ArrayList might contain arbitrary values
            [
                'filterValue' => new ArrayData(['SomeField' => 'some value']),
                'matchValue' => new ArrayData(['SomeField' => 'some value']),
                'modifiers' => [],
                'matches' => false,
            ],
            [
                'filterValue' => new ArrayData(['SomeField' => 'some value']),
                'matchValue' => new ArrayData(['SomeField' => 'SoMe VaLuE']),
                'modifiers' => [],
                'matches' => true,
            ],
        ];
        // negated
        foreach ($scenarios as $scenario) {
            $scenario['modifiers'][] = 'not';
            $scenario['matches'] = !$scenario['matches'];
            $scenarios[] = $scenario;
        }
        return $scenarios;
    }

    /**
     * @dataProvider provideMatches
     */
    public function testMatches(mixed $filterValue, mixed $matchValue, array $modifiers, bool $matches)
    {
        $filter = new LessThanFilter();
        $filter->setValue($filterValue);
        $filter->setModifiers($modifiers);
        $this->assertSame($matches, $filter->matches($matchValue));
    }
}
