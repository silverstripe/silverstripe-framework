<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\UnionList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Filterable;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\EquipmentCompany;
use SilverStripe\ORM\Tests\DataObjectTest\Fan;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Sortable;
use SilverStripe\ORM\Tests\DataObjectTest\SubTeam;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\DataObjectTest\TeamComment;
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
       
        $list1 = ValidatedObject::get()->filter(array('Name' => 'test obj 1'));
        $list2 = ValidatedObject::get()->filter(array('Name' => 'test obj 2'));
        $list3 = ValidatedObject::get();
        $this->unionList = UnionList::create(array($list1, $list2, $list3));
    }

    /*public function testFirst()
    {
    }*/

    /*public function testLast()
    {
    }*/

    public function testColumn()
    {
        $unionList = clone $this->unionList;
        $expected = [
            0 => 'test obj 1',
            1 => 'test obj 2',
            2 => 'test obj 1',
            3 => 'test obj 2',
        ];
        $this->assertEquals($expected, $unionList->column('Name'));
    }

    public function testCount()
    {
        $unionList = clone $this->unionList;
        $this->assertEquals(4, $unionList->count());
    }
}