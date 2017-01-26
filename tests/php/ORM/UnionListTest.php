<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\UnionList;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\ValidatedObject;

class UnionListTest extends SapphireTest
{
    /**
     * Borrow the model from DataObjectTest
     * {@inheritDoc}
     */
    protected static $fixture_file = 'DataObjectTest.yml';

    /**
     * @var UnionList
     */
    protected $unionList = null;

    protected function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects
        );
    }

    public function setUp()
    {
        parent::setUp();
        // create an object to test with
        $obj1 = new ValidatedObject();
        $obj1->Name = 'test obj 1';
        $obj1->write();
        $this->assertTrue($obj1->isInDB());

        $obj2 = new ValidatedObject();
        $obj2->Name = 'test obj 2';
        $obj2->write();
        $this->assertTrue($obj2->isInDB());

        $obj3 = new ValidatedObject();
        $obj3->Name = 'test obj 3';
        $obj3->write();
        $this->assertTrue($obj3->isInDB());

        $obj4 = new ValidatedObject();
        $obj4->Name = 'test obj 4';
        $obj4->write();
        $this->assertTrue($obj4->isInDB());
       
        $list1 = ValidatedObject::get()->filter(array('Name' => 'test obj 1'));
        $list2 = ValidatedObject::get()->filter(array('Name' => 'test obj 2'));
        $list3 = new ArrayList(array($obj3, $obj4));
        $this->unionList = UnionList::create(array($list1, $list2, $list3));
    }

    public function testFirst()
    {
        $unionList = $this->unionList;
        $obj1 = $unionList->first();
        $this->assertTrue($obj1->isInDB());
        $this->assertEquals('test obj 1', $obj1->Name);
    }

    public function testLast()
    {
        $unionList = $this->unionList;
        $obj1 = $unionList->last();
        $this->assertTrue($obj1->isInDB());
        $this->assertEquals('test obj 4', $obj1->Name);
    }

    public function testToArray()
    {
        $unionList = $this->unionList;
        $recordSet = $unionList->toArray();
        $this->assertEquals('test obj 1', $recordSet[0]->Name);
        $this->assertEquals('test obj 2', $recordSet[1]->Name);
        $this->assertEquals('test obj 3', $recordSet[2]->Name);
        $this->assertEquals('test obj 4', $recordSet[3]->Name);
    }

    public function testExists()
    {
        $unionList = $this->unionList;
        $this->assertEquals(true, $unionList->exists());
        $unionList = UnionList::create(array(ValidatedObject::get()->filter(array('Name' => 'non existant'))));
        $this->assertEquals(false, $unionList->exists());
    }

    public function testToNestedArray()
    {
        $unionList = $this->unionList;
        $recordSet = $unionList->toNestedArray();
        $expected = [
            0 => [
                'ID' => 1,
                'Name' => 'test obj 1',
            ],
            1 => [
                'ID' => 2,
                'Name' => 'test obj 2',
            ],
            2 => [
                'ID' => 3,
                'Name' => 'test obj 3',
            ],
            3 => [
                'ID' => 4,
                'Name' => 'test obj 4',
            ],
        ];
        foreach ($recordSet as $i => $record) {
            unset($recordSet[$i]['ClassName']);
            unset($recordSet[$i]['RecordClassName']);
            unset($recordSet[$i]['Created']);
            unset($recordSet[$i]['LastEdited']);
        }
        $this->assertEquals($expected, $recordSet);
    }

    public function testIterator()
    {
        $unionList = $this->unionList;
        $expected = [
            0 => 'test obj 1',
            1 => 'test obj 2',
            2 => 'test obj 3',
            3 => 'test obj 4',
        ];
        $counter = 0;
        foreach ($unionList as $i => $record) {
            // enforce numeric keys
            $this->assertEquals($counter, $i);
            $this->assertEquals($expected[$i], $record->Name);
            ++$counter;
        }
    }

    public function testColumn()
    {
        $unionList = $this->unionList;
        $expected = [
            0 => 'test obj 1',
            1 => 'test obj 2',
            2 => 'test obj 3',
            3 => 'test obj 4',
        ];
        $this->assertEquals($expected, $unionList->column('Name'));
    }

    public function testCount()
    {
        $unionList = $this->unionList;
        $this->assertEquals(4, $unionList->count());
    }
}