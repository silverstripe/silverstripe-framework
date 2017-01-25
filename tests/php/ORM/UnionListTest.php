<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\UnionList;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\ValidatedObject;

class UnionListTest extends SapphireTest
{

    // Borrow the model from DataObjectTest
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
        $unionList = clone $this->unionList;
        $obj1 = $unionList->first();
        $this->assertTrue($obj1->isInDB());
        $this->assertEquals('test obj 1', $obj1->Title);
    }

    public function testLast()
    {
        $unionList = clone $this->unionList;
        $obj1 = $unionList->first();
        $this->assertTrue($obj1->isInDB());
        $this->assertEquals('test obj 4', $obj1->Title);
    }

    public function testColumn()
    {
        $unionList = clone $this->unionList;
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
        $unionList = clone $this->unionList;
        $this->assertEquals(4, $unionList->count());
    }
}