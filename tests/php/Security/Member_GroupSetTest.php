<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Tests\GroupTest\TestMember;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use PHPUnit\Framework\Attributes\DataProvider;

class Member_GroupSetTest extends SapphireTest
{
    protected static $fixture_file = 'GroupTest.yml';

    protected static $extra_dataobjects = [
        TestMember::class
    ];

    #[DataProvider('provideForForeignIDPlaceholders')]
    public function testForForeignIDPlaceholders(bool $config, bool $useInt, bool $expected): void
    {
        Config::modify()->set(DataList::class, 'use_placeholders_for_integer_ids', $config);
        $member1 = $this->objFromFixture(TestMember::class, 'parentgroupuser');
        $member2 = $this->objFromFixture(TestMember::class, 'allgroupuser');
        $groups1 = $member1->Groups();
        $groups2 = $member2->Groups();
        $ids = $useInt ? [$member1->ID, $member2->ID] : ['Lorem', 'Ipsum'];
        $newGroupList = $groups1->forForeignID($ids);
        $sql = $newGroupList->dataQuery()->sql();
        preg_match('#ID" IN \(([^\)]+)\)\)#', $sql, $matches);
        $usesPlaceholders = ($matches[1] ?? '') === '?, ?, ?, ?, ?' || str_contains($sql, '"Group"."ID" = ?');
        $this->assertSame($expected, $usesPlaceholders);
        $expectedIDs = $useInt
            ? array_unique(array_merge($groups1->column('ID'), $groups2->column('ID')))
            : [];
        sort($expectedIDs);
        $actual = $newGroupList->sort('ID')->column('ID');
        $this->assertSame($expectedIDs, $actual);
    }

    public static function provideForForeignIDPlaceholders(): array
    {
        return [
            'config false' => [
                'config' => false,
                'useInt' => true,
                'expected' => false,
            ],
            'config false non-int' => [
                'config' => false,
                'useInt' => false,
                'expected' => true,
            ],
            'config true' => [
                'config' => true,
                'useInt' => true,
                'expected' => true,
            ],
            'config true non-int' => [
                'config' => true,
                'useInt' => false,
                'expected' => true,
            ],
        ];
    }
}
