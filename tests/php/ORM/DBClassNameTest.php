<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBClassName;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;

class DBClassNameTest extends SapphireTest
{

    protected $extraDataObjects = [
        DBClassNameTest\TestObject::class,
        DBClassNameTest\ObjectSubClass::class,
        DBClassNameTest\ObjectSubSubClass::class,
        DBClassNameTest\OtherClass::class,
        DBClassNameTest\CustomDefaultSubclass::class,
        DBClassNameTest\CustomDefault::class,
    ];

    /**
     * Test that custom subclasses generate the right hierarchy
     */
    public function testEnumList()
    {
        // Object 1 fields
        $object = new DBClassNameTest\TestObject();
        $defaultClass = $object->dbObject('DefaultClass');
        $anyClass = $object->dbObject('AnyClass');
        $childClass = $object->dbObject('ChildClass');
        $leafClass = $object->dbObject('LeafClass');

        // Object 2 fields
        $object2 = new DBClassNameTest\ObjectSubClass();
        $midDefault = $object2->dbObject('MidClassDefault');
        $midClass = $object2->dbObject('MidClass');

        // Default fields always default to children of base class (even if put in a subclass)
        $mainSubclasses = array (
            DBClassNameTest\TestObject::class => DBClassNameTest\TestObject::class,
            DBClassNameTest\ObjectSubClass::class => DBClassNameTest\ObjectSubClass::class,
            DBClassNameTest\ObjectSubSubClass::class => DBClassNameTest\ObjectSubSubClass::class,
        );
        $this->assertEquals($mainSubclasses, $defaultClass->getEnumObsolete());
        $this->assertEquals($mainSubclasses, $midDefault->getEnumObsolete());

        // Unbound classes detect any
        $anyClasses = $anyClass->getEnumObsolete();
        $this->assertContains(DBClassNameTest\OtherClass::class, $anyClasses);
        $this->assertContains(DBClassNameTest\TestObject::class, $anyClasses);
        $this->assertContains(DBClassNameTest\ObjectSubClass::class, $anyClasses);
        $this->assertContains(DBClassNameTest\ObjectSubSubClass::class, $anyClasses);

        // Classes bound to the middle of a tree
        $midSubClasses = $mainSubclasses = array (
            DBClassNameTest\ObjectSubClass::class => DBClassNameTest\ObjectSubClass::class,
            DBClassNameTest\ObjectSubSubClass::class => DBClassNameTest\ObjectSubSubClass::class,
        );
        $this->assertEquals($midSubClasses, $childClass->getEnumObsolete());
        $this->assertEquals($midSubClasses, $midClass->getEnumObsolete());

        // Leaf clasess contain only exactly one node
        $this->assertEquals(
            array(DBClassNameTest\ObjectSubSubClass::class => DBClassNameTest\ObjectSubSubClass::class,),
            $leafClass->getEnumObsolete()
        );
    }

    /**
     * Test that the base class can be detected under various circumstances
     */
    public function testBaseClassDetection()
    {
        // Explicit DataObject
        $field1 = new DBClassName('MyClass', DataObject::class);
        $this->assertEquals(DataObject::class, $field1->getBaseClass());
        $this->assertNotEquals(DataObject::class, $field1->getDefault());

        // Explicit base class
        $field2 = new DBClassName('MyClass', DBClassNameTest\TestObject::class);
        $this->assertEquals(DBClassNameTest\TestObject::class, $field2->getBaseClass());
        $this->assertEquals(DBClassNameTest\TestObject::class, $field2->getDefault());

        // Explicit subclass
        $field3 = new DBClassName('MyClass');
        $field3->setValue(null, new DBClassNameTest\ObjectSubClass());
        $this->assertEquals(DBClassNameTest\TestObject::class, $field3->getBaseClass());
        $this->assertEquals(DBClassNameTest\TestObject::class, $field3->getDefault());

        // Implicit table
        $field4 = new DBClassName('MyClass');
        $field4->setTable('DBClassNameTest_ObjectSubClass_Versions');
        $this->assertEquals(DBClassNameTest\TestObject::class, $field4->getBaseClass());
        $this->assertEquals(DBClassNameTest\TestObject::class, $field4->getDefault());

        // Missing
        $field5 = new DBClassName('MyClass');
        $this->assertEquals(DataObject::class, $field5->getBaseClass());
        $this->assertNotEquals(DataObject::class, $field5->getDefault());

        // Invalid class
        $field6 = new DBClassName('MyClass');
        $field6->setTable('InvalidTable');
        $this->assertEquals(DataObject::class, $field6->getBaseClass());
        $this->assertNotEquals(DataObject::class, $field6->getDefault());

        // Custom default_classname
        $field7 = new DBClassName('MyClass');
        $field7->setTable('DBClassNameTest_CustomDefault');
        $this->assertEquals(DBClassNameTest\CustomDefault::class, $field7->getBaseClass());
        $this->assertEquals(DBClassNameTest\CustomDefaultSubclass::class, $field7->getDefault());
    }
}
