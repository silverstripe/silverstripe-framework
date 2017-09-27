<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

class DataObjectDuplicationTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected static $extra_dataobjects = array(
        DataObjectDuplicationTest\Class1::class,
        DataObjectDuplicationTest\Class2::class,
        DataObjectDuplicationTest\Class3::class,
        DataObjectDuplicationTest\Class4::class,
    );

    public function testDuplicate()
    {
        DBDatetime::set_mock_now('2016-01-01 01:01:01');
        $orig = new DataObjectDuplicationTest\Class1();
        $orig->text = 'foo';
        $orig->write();
        DBDatetime::set_mock_now('2016-01-02 01:01:01');
        $duplicate = $orig->duplicate();
        $this->assertInstanceOf(
            DataObjectDuplicationTest\Class1::class,
            $duplicate,
            'Creates the correct type'
        );
        $this->assertNotEquals(
            $duplicate->ID,
            $orig->ID,
            'Creates a unique record'
        );
        $this->assertEquals(
            'foo',
            $duplicate->text,
            'Copies fields'
        );
        $this->assertEquals(
            2,
            DataObjectDuplicationTest\Class1::get()->Count(),
            'Only creates a single duplicate'
        );
        $this->assertEquals(DBDatetime::now()->Nice(), $duplicate->dbObject('Created')->Nice());
        $this->assertNotEquals($orig->dbObject('Created')->Nice(), $duplicate->dbObject('Created')->Nice());
    }

    public function testDuplicateHasOne()
    {
        $relationObj = new DataObjectDuplicationTest\Class1();
        $relationObj->text = 'class1';
        $relationObj->write();

        $orig = new DataObjectDuplicationTest\Class2();
        $orig->text = 'class2';
        $orig->oneID = $relationObj->ID;
        $orig->write();

        $duplicate = $orig->duplicate();
        $this->assertEquals(
            $relationObj->ID,
            $duplicate->oneID,
            'Copies has_one relationship'
        );
        $this->assertEquals(
            2,
            DataObjectDuplicationTest\Class2::get()->Count(),
            'Only creates a single duplicate'
        );
        $this->assertEquals(
            1,
            DataObjectDuplicationTest\Class1::get()->Count(),
            'Does not create duplicate of has_one relationship'
        );
    }


    public function testDuplicateManyManyClasses()
    {
        //create new test classes below
        $one = new DataObjectDuplicationTest\Class1();
        $two = new DataObjectDuplicationTest\Class2();
        $three = new DataObjectDuplicationTest\Class3();

        //set some simple fields
        $text1 = "Test Text 1";
        $text2 = "Test Text 2";
        $text3 = "Test Text 3";
        $one->text = $text1;
        $two->text = $text2;
        $three->text = $text3;

        //write the to DB
        $one->write();
        $two->write();
        $three->write();

        //create relations
        $one->twos()->add($two);
        $one->threes()->add($three);

        $one = DataObject::get_by_id(DataObjectDuplicationTest\Class1::class, $one->ID);
        $two = DataObject::get_by_id(DataObjectDuplicationTest\Class2::class, $two->ID);
        $three = DataObject::get_by_id(DataObjectDuplicationTest\Class3::class, $three->ID);

        //test duplication
        $oneCopy = $one->duplicate(true, true);
        $twoCopy = $two->duplicate(true, true);
        $threeCopy = $three->duplicate(true, true);

        $oneCopy = DataObject::get_by_id(DataObjectDuplicationTest\Class1::class, $oneCopy->ID);
        $twoCopy = DataObject::get_by_id(DataObjectDuplicationTest\Class2::class, $twoCopy->ID);
        $threeCopy = DataObject::get_by_id(DataObjectDuplicationTest\Class3::class, $threeCopy->ID);

        $this->assertNotNull($oneCopy, "Copy of 1 exists");
        $this->assertNotNull($twoCopy, "Copy of 2 exists");
        $this->assertNotNull($threeCopy, "Copy of 3 exists");

        $this->assertEquals($text1, $oneCopy->text);
        $this->assertEquals($text2, $twoCopy->text);
        $this->assertEquals($text3, $threeCopy->text);

        $this->assertNotEquals(
            $one->twos()->Count(),
            $oneCopy->twos()->Count(),
            "Many-to-one relation not copied (has_many)"
        );
        $this->assertEquals(
            $one->threes()->Count(),
            $oneCopy->threes()->Count(),
            "Object has the correct number of relations"
        );
        $this->assertEquals(
            $three->ones()->Count(),
            $threeCopy->ones()->Count(),
            "Object has the correct number of relations"
        );

        $this->assertEquals(
            $one->ID,
            $twoCopy->one()->ID,
            "Match between relation of copy and the original"
        );
        $this->assertEquals(
            0,
            $oneCopy->twos()->Count(),
            "Many-to-one relation not copied (has_many)"
        );
        $this->assertEquals(
            $three->ID,
            $oneCopy->threes()->First()->ID,
            "Match between relation of copy and the original"
        );
        $this->assertEquals(
            $one->ID,
            $threeCopy->ones()->First()->ID,
            "Match between relation of copy and the original"
        );
    }

    public function testDuplicateManyManyFiltered()
    {
        $parent = new DataObjectDuplicationTest\Class4();
        $parent->Title = 'Parent';
        $parent->write();

        $child = new DataObjectDuplicationTest\Class4();
        $child->Title = 'Child';
        $child->write();

        $grandChild = new DataObjectDuplicationTest\Class4();
        $grandChild->Title = 'GrandChild';
        $grandChild->write();

        $parent->Children()->add($child);
        $child->Children()->add($grandChild);

        // Duplcating $child should only duplicate grandchild
        $childDuplicate = $child->duplicate(true, 'many_many');
        $this->assertEquals(0, $childDuplicate->Parents()->count());
        $this->assertListEquals(
            [['Title' => 'GrandChild']],
            $childDuplicate->Children()
        );

        // Duplicate belongs_many_many only
        $belongsDuplicate = $child->duplicate(true, 'belongs_many_many');
        $this->assertEquals(0, $belongsDuplicate->Children()->count());
        $this->assertListEquals(
            [['Title' => 'Parent']],
            $belongsDuplicate->Parents()
        );

        // Duplicate all
        $allDuplicate = $child->duplicate(true, true);
        $this->assertListEquals(
            [['Title' => 'Parent']],
            $allDuplicate->Parents()
        );
        $this->assertListEquals(
            [['Title' => 'GrandChild']],
            $allDuplicate->Children()
        );
    }

    /**
     * Test duplication of UnsavedRelations
     */
    public function testDuplicateUnsaved()
    {
        $one = new DataObjectDuplicationTest\Class1();
        $one->text = "Test Text 1";
        $three = new DataObjectDuplicationTest\Class3();
        $three->text = "Test Text 3";
        $one->threes()->add($three);
        $this->assertListEquals(
            [['text' => 'Test Text 3']],
            $one->threes()
        );
        // Test duplicate
        $dupe = $one->duplicate(false, true);
        $this->assertEquals('Test Text 1', $dupe->text);
        $this->assertListEquals(
            [['text' => 'Test Text 3']],
            $dupe->threes()
        );
    }
}
