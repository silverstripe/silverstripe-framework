<?php

namespace SilverStripe\View\Tests;

use SilverStripe\Dev\Constraint\ViewableDataContains;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData_Customised;

/**
 * Test for ViewableData_Customised.
 */
class ViewableDataCustomisedTest extends SapphireTest
{
    public function testNestedViewableDataCustomisedAsCustomised()
    {
        $outerCustomised = ViewableData_Customised::create($this->makeOuterOriginal(), $this->makeInnerViewableDataCustomised());
        $this->assertThat($outerCustomised, $this->makeTestConstraint());
    }

    public function testNestedViewableDataCustomisedAsOriginal()
    {
        $outerCustomised = ViewableData_Customised::create($this->makeInnerViewableDataCustomised(), $this->makeOuterOriginal());
        $this->assertThat($outerCustomised, $this->makeTestConstraint());
    }

    private function makeTestConstraint()
    {
        return new ViewableDataContains([
            'outerOriginal'   => 'foobar',
            'innerOriginal'   => 'hello',
            'innerCustomised' => 'world',
        ]);
    }

    private function makeOuterOriginal()
    {
        return ArrayData::create([
            'outerOriginal' => 'foobar',
        ]);
    }

    private function makeInnerViewableDataCustomised()
    {
        $original = ArrayData::create([
            'innerOriginal' => 'hello',
        ]);

        $customised = ArrayData::create([
            'innerCustomised' => 'world',
        ]);

        return ViewableData_Customised::create($original, $customised);
    }
}
