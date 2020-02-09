<?php

namespace SilverStripe\Tests\UniqueKey;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

class ServiceTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        River::class,
        Mountain::class,
    ];

    /**
     * @param int $id
     * @param string $class
     * @param bool $extraKeys
     * @param string $expected
     * @dataProvider uniqueKeysProvider
     */
    public function testUniqueKey(int $id, string $class, bool $extraKeys, string $expected): void
    {
        if ($extraKeys) {
            $class::add_extension(ExtraKeysExtension::class);
        }

        /** @var DataObject $object */
        $object = Injector::inst()->create($class);
        $object->ID = $id;

        $this->assertEquals($expected, $object->getUniqueKey());

        if ($extraKeys) {
            $class::remove_extension(ExtraKeysExtension::class);
        }
    }

    public function uniqueKeysProvider(): array
    {
        return [
            [1, River::class, false, 'ss-River-1-7eab00006ab6d090635b03f9fa1187d7'],
            [1, River::class, true, 'ss-River-1-65474ab87fd42ca8cbfc32f87d5840e7'],
            [2, River::class, false, 'ss-River-2-9c63d549d3a7a2f9679f7ce0dbb6a177'],
            [2, River::class, true, 'ss-River-2-a028c9b5ecd2dd68edc6f20192e29c63'],
            [1, Mountain::class, false, 'ss-Mountain-1-013d8ba56604ceeb2bda4b09d04c7e29'],
            [1, Mountain::class, true, 'ss-Mountain-1-3dba35f13a9d3ad648be466946297444'],
            [2, Mountain::class, false, 'ss-Mountain-2-a628f2db748065729d6a832a094cea3f'],
            [2, Mountain::class, true, 'ss-Mountain-2-e6236799ab5a00d36ee704fa87d46021'],
        ];
    }
}
