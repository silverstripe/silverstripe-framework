<?php

namespace SilverStripe\ORM\Tests\Connect;

use ReflectionMethod;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\DBQueryBuilder;

class DBQueryBuilderTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function provideShouldBuildTraceComment(): array
    {
        return [
            [
                'envValue' => null,
                'yamlValue' => true,
                'expected' => true,
            ],
            [
                'envValue' => null,
                'yamlValue' => false,
                'expected' => false,
            ],
            [
                'envValue' => true,
                'yamlValue' => true,
                'expected' => true,
            ],
            [
                'envValue' => true,
                'yamlValue' => false,
                'expected' => true,
            ],
            [
                'envValue' => false,
                'yamlValue' => false,
                'expected' => false,
            ],
            [
                'envValue' => false,
                'yamlValue' => true,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideShouldBuildTraceComment
     */
    public function testShouldBuildTraceComment(?bool $envValue, bool $yamlValue, bool $expected): void
    {
        $queryBuilder = new DBQueryBuilder();
        $reflectionMethod = new ReflectionMethod($queryBuilder, 'shouldBuildTraceComment');
        $reflectionMethod->setAccessible(true);

        if ($envValue !== null) {
            Environment::setEnv('SS_TRACE_DB_QUERY_ORIGIN', $envValue);
        }
        DBQueryBuilder::config()->set('trace_query_origin', $yamlValue);

        $this->assertSame($expected, $reflectionMethod->invoke($queryBuilder));
    }
}
