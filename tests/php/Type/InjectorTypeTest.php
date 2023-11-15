<?php

namespace SilverStripe\Type\Tests;

use Generator;
use PHPStan\Testing\TypeInferenceTestCase;
use function glob;

class InjectorTypeTest extends TypeInferenceTestCase
{
    public function typeFileAsserts(): Generator
    {
        $typeTests = glob(__DIR__ . '/data/injector-types.php') ?: [];

        foreach ($typeTests as $typeTest) {
            yield from $this->gatherAssertTypes($typeTest);
        }
    }

    /**
     * @dataProvider typeFileAsserts
     */
    public function testFileAsserts(string $assertType, string $file, mixed ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
