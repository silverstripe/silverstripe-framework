<?php

namespace SilverStripe\Tests\ORM\UniqueKey;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use PHPUnit\Framework\Attributes\DataProvider;

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
     */
    #[DataProvider('uniqueKeysProvider')]
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

    public static function uniqueKeysProvider(): array
    {
        return [
            [1, River::class, false, 'River-1-8d3310e232f75a01f5a0c9344655263d'],
            [1, River::class, true, 'River-1-ff2ea6e873a9e28538dd4af278f35e08'],
            [2, River::class, false, 'River-2-c562c31e5c2caaabb124b46e274097c1'],
            [2, River::class, true, 'River-2-410c1eb12697a26742bbe4b059625ab2'],
            [1, Mountain::class, false, 'Mountain-1-93164c0f65fa28778fb75163c1e3e2f0'],
            [1, Mountain::class, true, 'Mountain-1-2daf208e0b89252e5d239fbc0464a517'],
            [2, Mountain::class, false, 'Mountain-2-62366f2b970a64de6f2a8e8654f179d5'],
            [2, Mountain::class, true, 'Mountain-2-a724046b14d331a1486841eaa591d109'],
        ];
    }
}
