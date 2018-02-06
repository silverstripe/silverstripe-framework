<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;

class DataObjectDuplicationTest extends SapphireTest
{
    protected static $fixture_file = 'DataObjectDuplicationTest.yml';

    protected static $extra_dataobjects = [
        DataObjectDuplicationTest\Antelope::class,
        DataObjectDuplicationTest\Bobcat::class,
        DataObjectDuplicationTest\Caribou::class,
        DataObjectDuplicationTest\Dingo::class,
        DataObjectDuplicationTest\Elephant::class,
        DataObjectDuplicationTest\Frog::class,
        DataObjectDuplicationTest\Goat::class,
    ];

    public function testDuplicate()
    {
        /** @var DataObjectDuplicationTest\Antelope $orig */
        $orig = $this->objFromFixture(DataObjectDuplicationTest\Antelope::class, 'one');
        /** @var DataObjectDuplicationTest\Antelope $duplicate */
        $duplicate = $orig->duplicate();
        $this->assertInstanceOf(
            DataObjectDuplicationTest\Antelope::class,
            $duplicate,
            'Creates the correct type'
        );
        $this->assertNotEquals(
            $duplicate->ID,
            $orig->ID,
            'Creates a unique record'
        );

        // Check 'bobcats' relation duplicated
        $twoOne = $this->objFromFixture(DataObjectDuplicationTest\Bobcat::class, 'one');
        $twoTwo = $this->objFromFixture(DataObjectDuplicationTest\Bobcat::class, 'two');
        $this->assertListEquals(
            [
                ['Title' => 'Bobcat two'],
                ['Title' => 'Bobcat three'],
            ],
            $duplicate->bobcats()
        );
        $this->assertEmpty(
            array_intersect(
                $orig->bobcats()->getIDList(),
                $duplicate->bobcats()->getIDList()
            )
        );
        /** @var DataObjectDuplicationTest\Bobcat $twoTwoDuplicate */
        $twoTwoDuplicate = $duplicate->bobcats()->filter('Title', 'Bobcat two')->first();
        $this->assertNotEmpty($twoTwoDuplicate);
        $this->assertNotEquals($twoTwo->ID, $twoTwoDuplicate->ID);

        // Check 'bobcats.self' relation duplicated
        /** @var DataObjectDuplicationTest\Bobcat $twoOneDuplicate */
        $twoOneDuplicate = $twoTwoDuplicate->self();
        $this->assertNotEmpty($twoOneDuplicate);
        $this->assertNotEquals($twoOne->ID, $twoOneDuplicate->ID);

        // Ensure 'bobcats.seven' instance is not duplicated
        $sevenOne = $this->objFromFixture(DataObjectDuplicationTest\Goat::class, 'one');
        $sevenTwo = $this->objFromFixture(DataObjectDuplicationTest\Goat::class, 'two');
        $this->assertEquals($sevenOne->ID, $twoOneDuplicate->goat()->ID);
        $this->assertEquals($sevenTwo->ID, $twoTwoDuplicate->goat()->ID);

        // Ensure that 'caribou' many_many list exists on both, but only the mapping table is duplicated
        // many_many_extraFields are also duplicated
        $caribouList = [
            [
                'Title' => 'Caribou one',
                'Sort' => 4,
            ],
            [
                'Title' => 'Caribou two',
                'Sort' => 5,
            ],
        ];
        // Original and duplicate lists have the same content
        $this->assertListEquals(
            $caribouList,
            $orig->caribou()
        );
        $this->assertListEquals(
            $caribouList,
            $duplicate->caribou()
        );
        // Ids of each record are the same (only mapping content is duplicated)
        $this->assertEquals(
            $orig->caribou()->getIDList(),
            $duplicate->caribou()->getIDList()
        );

        // Ensure 'five' belongs_to is duplicated
        $fiveOne = $this->objFromFixture(DataObjectDuplicationTest\Elephant::class, 'one');
        $fiveOneDuplicate = $duplicate->elephant();
        $this->assertNotEmpty($fiveOneDuplicate);
        $this->assertEquals('Elephant one', $fiveOneDuplicate->Title);
        $this->assertNotEquals($fiveOne->ID, $fiveOneDuplicate->ID);

        // Ensure 'five.Child' is duplicated
        $sixOne = $this->objFromFixture(DataObjectDuplicationTest\Frog::class, 'one');
        $sixOneDuplicate = $fiveOneDuplicate->Child();
        $this->assertNotEmpty($sixOneDuplicate);
        $this->assertEquals('Frog one', $sixOneDuplicate->Title);
        $this->assertNotEquals($sixOne->ID, $sixOneDuplicate->ID);
    }
}
