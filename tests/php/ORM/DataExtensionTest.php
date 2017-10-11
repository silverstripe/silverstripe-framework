<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

class DataExtensionTest extends SapphireTest
{
    protected static $fixture_file = 'DataExtensionTest.yml';

    protected static $extra_dataobjects = array(
        DataExtensionTest\TestMember::class,
        DataExtensionTest\Player::class,
        DataExtensionTest\RelatedObject::class,
        DataExtensionTest\MyObject::class,
        DataExtensionTest\CMSFieldsBase::class,
        DataExtensionTest\CMSFieldsChild::class,
        DataExtensionTest\CMSFieldsGrandChild::class
    );

    protected static $required_extensions = [
        DataObject::class => [
            DataExtensionTest\AppliedToDO::class,
        ]
    ];

    public function testOneToManyAssociationWithExtension()
    {
        $contact = new DataExtensionTest\TestMember();
        $contact->Website = "http://www.example.com";

        $object = new DataExtensionTest\RelatedObject();
        $object->FieldOne = "Lorem ipsum dolor";
        $object->FieldTwo = "Random notes";

        // The following code doesn't currently work:
        // $contact->RelatedObjects()->add($object);
        // $contact->write();

        // Instead we have to do the following
        $contact->write();
        $object->ContactID = $contact->ID;
        $object->write();

        $contactID = $contact->ID;
        unset($contact);
        unset($object);

        $contact = DataObject::get_one(
            DataExtensionTest\TestMember::class,
            array(
            '"DataExtensionTest_Member"."Website"' => 'http://www.example.com'
            )
        );
        $object = DataObject::get_one(
            DataExtensionTest\RelatedObject::class,
            array(
            '"DataExtensionTest_RelatedObject"."ContactID"' => $contactID
            )
        );

        $this->assertNotNull($object, 'Related object not null');
        $this->assertInstanceOf(
            DataExtensionTest\TestMember::class,
            $object->Contact(),
            'Related contact is a member dataobject'
        );
        $this->assertInstanceOf(
            DataExtensionTest\TestMember::class,
            $object->getComponent('Contact'),
            'getComponent does the same thing as Contact()'
        );

        $this->assertInstanceOf(DataExtensionTest\RelatedObject::class, $contact->RelatedObjects()->First());
        $this->assertEquals("Lorem ipsum dolor", $contact->RelatedObjects()->First()->FieldOne);
        $this->assertEquals("Random notes", $contact->RelatedObjects()->First()->FieldTwo);
        $contact->delete();
    }

    public function testManyManyAssociationWithExtension()
    {
        $parent = new DataExtensionTest\MyObject();
        $parent->Title = 'My Title';
        $parent->write();

        $this->assertEquals(0, $parent->Faves()->Count());

        $obj1 = $this->objFromFixture(DataExtensionTest\RelatedObject::class, 'obj1');
        $obj2 = $this->objFromFixture(DataExtensionTest\RelatedObject::class, 'obj2');

        $parent->Faves()->add($obj1->ID);
        $this->assertEquals(1, $parent->Faves()->Count());

        $parent->Faves()->add($obj2->ID);
        $this->assertEquals(2, $parent->Faves()->Count());

        $parent->Faves()->removeByID($obj2->ID);
        $this->assertEquals(1, $parent->Faves()->Count());
    }

    /**
     * Test {@link Object::add_extension()} has loaded DataExtension statics correctly.
     */
    public function testAddExtensionLoadsStatics()
    {
        // Object::add_extension() will load DOD statics directly, so let's try adding a extension on the fly
        DataExtensionTest\Player::add_extension(
            DataExtensionTest\PlayerExtension::class
        );

        // Now that we've just added the extension, we need to rebuild the database
        static::resetDBSchema(true);

        // Create a test record with extended fields, writing to the DB
        $player = new DataExtensionTest\Player();
        $player->setField('Name', 'Joe');
        $player->setField('DateBirth', '1990-5-10');
        $player->Address = '123 somewhere street';
        $player->write();

        unset($player);

        // Pull the record out of the DB and examine the extended fields
        $player = DataObject::get_one(
            DataExtensionTest\Player::class,
            [ '"DataExtensionTest_Player"."Name"' => 'Joe' ]
        );
        $this->assertEquals('1990-05-10', $player->DateBirth);
        $this->assertEquals('123 somewhere street', $player->Address);
        $this->assertEquals('Goalie', $player->Status);
    }

    /**
     * Test that DataObject::$api_access can be set to true via a extension
     */
    public function testApiAccessCanBeExtended()
    {
        $this->assertTrue(Config::inst()->get(
            DataExtensionTest\TestMember::class,
            'api_access'
        ));
    }

    public function testPermissionExtension()
    {
        // testing behaviour in isolation, too many sideeffects and other checks
        // in SiteTree->can*() methods to test one single feature reliably with them

        $obj = $this->objFromFixture(DataExtensionTest\MyObject::class, 'object1');
        $websiteuser = $this->objFromFixture(Member::class, 'websiteuser');
        $admin = $this->objFromFixture(Member::class, 'admin');

        $this->assertFalse(
            $obj->canOne($websiteuser),
            'Both extensions return true, but original method returns false'
        );

        $this->assertFalse(
            $obj->canTwo($websiteuser),
            'One extension returns false, original returns true, but extension takes precedence'
        );

        $this->assertTrue(
            $obj->canThree($admin),
            'Undefined extension methods returning NULL dont influence the original method'
        );
    }

    public function testPopulateDefaults()
    {
        $obj = new DataExtensionTest\TestMember();
        $this->assertEquals(
            $obj->Phone,
            '123',
            'Defaults can be populated through extension'
        );
    }

    /**
     * Test that DataObject::dbObject() works for fields applied by a extension
     */
    public function testDbObjectOnExtendedFields()
    {
        $member = $this->objFromFixture(DataExtensionTest\TestMember::class, 'member1');
        $this->assertNotNull($member->dbObject('Website'));
        $this->assertInstanceOf('SilverStripe\\ORM\\FieldType\\DBVarchar', $member->dbObject('Website'));
    }

    public function testExtensionCanBeAppliedToDataObject()
    {
        $do = new DataObject();
        $mo = new DataExtensionTest\MyObject();

        $this->assertTrue($do->hasMethod('testMethodApplied'));
        $this->assertTrue($mo->hasMethod('testMethodApplied'));

        $this->assertEquals("hello world", $mo->testMethodApplied());
        $this->assertEquals("hello world", $do->testMethodApplied());
    }

    public function testExtensionAllMethodNamesHasOwner()
    {
        /** @var DataExtensionTest\MyObject $do */
        $do = DataExtensionTest\MyObject::create();

        $this->assertTrue($do->hasMethod('getTestValueWith_MyObject'));
    }

    public function testPageFieldGeneration()
    {
        $page = new DataExtensionTest\CMSFieldsBase();
        $fields = $page->getCMSFields();
        $this->assertNotEmpty($fields);

        // Check basic field exists
        $this->assertNotEmpty($fields->dataFieldByName('PageField'));
    }

    public function testPageExtensionsFieldGeneration()
    {
        $page = new DataExtensionTest\CMSFieldsBase();
        $fields = $page->getCMSFields();
        $this->assertNotEmpty($fields);

        // Check extending fields exist
        $this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldRemove')); // Not removed yet!
        $this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldKeep'));
    }

    public function testSubpageFieldGeneration()
    {
        $page = new DataExtensionTest\CMSFieldsChild();
        $fields = $page->getCMSFields();
        $this->assertNotEmpty($fields);

        // Check extending fields exist
        $this->assertEmpty($fields->dataFieldByName('ExtendedFieldRemove')); // Removed by child class
        $this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldKeep'));
        $this->assertNotEmpty($preExtendedField = $fields->dataFieldByName('ChildFieldBeforeExtension'));
        $this->assertEquals($preExtendedField->Title(), 'ChildFieldBeforeExtension: Modified Title');

        // Post-extension fields
        $this->assertNotEmpty($fields->dataFieldByName('ChildField'));
    }

    public function testSubSubpageFieldGeneration()
    {
        $page = new DataExtensionTest\CMSFieldsGrandChild();
        $fields = $page->getCMSFields();
        $this->assertNotEmpty($fields);

        // Check extending fields exist
        $this->assertEmpty($fields->dataFieldByName('ExtendedFieldRemove')); // Removed by child class
        $this->assertNotEmpty($fields->dataFieldByName('ExtendedFieldKeep'));

        // Check child fields removed by grandchild in beforeUpdateCMSFields
        $this->assertEmpty($fields->dataFieldByName('ChildFieldBeforeExtension')); // Removed by grandchild class

        // Check grandchild field modified by extension
        $this->assertNotEmpty($preExtendedField = $fields->dataFieldByName('GrandchildFieldBeforeExtension'));
        $this->assertEquals($preExtendedField->Title(), 'GrandchildFieldBeforeExtension: Modified Title');

        // Post-extension fields
        $this->assertNotEmpty($fields->dataFieldByName('ChildField'));
        $this->assertNotEmpty($fields->dataFieldByName('GrandchildField'));
    }

    /**
     * Test setOwner behaviour
     */
    public function testSetOwner()
    {
        $extension = new DataExtensionTest\Extension1();
        $obj1 = $this->objFromFixture(DataExtensionTest\RelatedObject::class, 'obj1');
        $obj2 = $this->objFromFixture(DataExtensionTest\RelatedObject::class, 'obj1');

        $extension->setOwner(null);
        $this->assertNull($extension->getOwner());

        // Set original owner
        $extension->setOwner($obj1);
        $this->assertEquals($obj1, $extension->getOwner());

        // Set nested owner
        $extension->setOwner($obj2);
        $this->assertEquals($obj2, $extension->getOwner());

        // Clear nested owner
        $extension->clearOwner();
        $this->assertEquals($obj1, $extension->getOwner());

        // Clear pushed null
        $extension->clearOwner();
        $this->assertNull($extension->getOwner());

        // Clear original null
        $extension->clearOwner();
        $this->assertNull($extension->getOwner());

        // Another clearOwner should error
        $this->expectExceptionMessage(\BadMethodCallException::class);
        $this->expectExceptionMessage('clearOwner() called more than setOwner()');
        $extension->clearOwner();
    }
}
