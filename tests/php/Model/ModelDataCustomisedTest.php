<?php

namespace SilverStripe\Model\Tests;

use SilverStripe\Dev\Constraint\ModelDataContains;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\ModelDataCustomised;

/**
 * Test for ModelDataCustomised.
 */
class ModelDataCustomisedTest extends SapphireTest
{
    public function testNestedModelDataCustomisedAsCustomised()
    {
        $outerCustomised = ModelDataCustomised::create($this->makeOuterOriginal(), $this->makeInnerModelDataCustomised());
        $this->assertThat($outerCustomised, $this->makeTestConstraint());
    }

    public function testNestedModelDataCustomisedAsOriginal()
    {
        $outerCustomised = ModelDataCustomised::create($this->makeInnerModelDataCustomised(), $this->makeOuterOriginal());
        $this->assertThat($outerCustomised, $this->makeTestConstraint());
    }

    private function makeTestConstraint()
    {
        return new ModelDataContains([
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

    private function makeInnerModelDataCustomised()
    {
        $original = ArrayData::create([
            'innerOriginal' => 'hello',
        ]);

        $customised = ArrayData::create([
            'innerCustomised' => 'world',
        ]);

        return ModelDataCustomised::create($original, $customised);
    }
}
