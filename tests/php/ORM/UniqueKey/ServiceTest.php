<?php

namespace SilverStripe\Tests\ORM\UniqueKey;

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
            [1, River::class, false, 'ss-River-1-e64cc160ce00cc28cb0f8a3096cf3ed5'],
            [1, River::class, true, 'ss-River-1-1484f5b9c7d403b7fd2ba944efead0a6'],
            [2, River::class, false, 'ss-River-2-93608031dbdb53167fce1c700e71adfd'],
            [2, River::class, true, 'ss-River-2-cfb8c8328ca792cfe83859b0ef28d3f4'],
            [1, Mountain::class, false, 'ss-Mountain-1-8d1e32d7d9a5f55b9c5e87facc6a0acc'],
            [1, Mountain::class, true, 'ss-Mountain-1-7d286845ff54b023fb43450ecd55aeb8'],
            [2, Mountain::class, false, 'ss-Mountain-2-813dc6d6a905b6d3720130b9fb46e01a'],
            [2, Mountain::class, true, 'ss-Mountain-2-d1133d717d00c944732ac25e6043ce5e'],
        ];
    }
}
