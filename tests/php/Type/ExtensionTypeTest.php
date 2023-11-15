<?php

namespace SilverStripe\Type\Tests;

use Generator;
use PHPStan\Testing\TypeInferenceTestCase;
use SilverStripe\Core\Config\Config;
use SilverStripe\Type\Tests\Source\ExtensibleMockOne;
use SilverStripe\Type\Tests\Source\ExtensibleMockTwo;
use SilverStripe\Type\Tests\Source\ExtensionMockOne;
use SilverStripe\Type\Tests\Source\ExtensionMockTwo;
use function glob;

class ExtensionTypeTest extends TypeInferenceTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::modify()->merge(
            ExtensibleMockOne::class,
            'extensions',
            [
                ExtensionMockOne::class,
                ExtensionMockTwo::class,
            ]
        );

        Config::modify()->merge(
            ExtensibleMockTwo::class,
            'extensions',
            [
                ExtensionMockTwo::class,
            ]
        );
    }

    public function typeFileAsserts(): Generator
    {
        $typeTests = glob(__DIR__ . '/data/extension-types.php') ?: [];

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

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan.neon.dist'];
    }
}
