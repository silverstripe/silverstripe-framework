<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\Versioning\ChangeSetItem;
use SilverStripe\Dev\SapphireTest;

class ChangeSetItemTest extends SapphireTest
{

    protected $extraDataObjects = [
        ChangeSetItemTest\VersionedObject::class
    ];

    public function testChangeType()
    {
        $this->logInWithPermission('ADMIN');
        $object = new ChangeSetItemTest\VersionedObject(['Foo' => 1]);
        $object->write();

        $item = new ChangeSetItem(
            [
            'ObjectID' => $object->ID,
            'ObjectClass' => $object->baseClass(),
            ]
        );

        $this->assertEquals(
            ChangeSetItem::CHANGE_CREATED,
            $item->ChangeType,
            'New objects that aren\'t yet published should return created'
        );

        $object->publishRecursive();

        $this->assertEquals(
            ChangeSetItem::CHANGE_NONE,
            $item->ChangeType,
            'Objects that have just been published should return no change'
        );

        $object->Foo += 1;
        $object->write();

        $this->assertEquals(
            ChangeSetItem::CHANGE_MODIFIED,
            $item->ChangeType,
            'Object that have unpublished changes written to draft should show as modified'
        );

        $object->publishRecursive();

        $this->assertEquals(
            ChangeSetItem::CHANGE_NONE,
            $item->ChangeType,
            'Objects that have just been published should return no change'
        );

        // We need to use a copy, because ID is set to 0 by delete, causing the following unpublish to fail
        $objectCopy = clone $object;
        $objectCopy->delete();

        $this->assertEquals(
            ChangeSetItem::CHANGE_DELETED,
            $item->ChangeType,
            'Objects that have been deleted from draft (but not yet unpublished) should show as deleted'
        );

        $object->doUnpublish();

        $this->assertEquals(
            ChangeSetItem::CHANGE_NONE,
            $item->ChangeType,
            'Objects that have been deleted and then unpublished should return no change'
        );
    }

    public function testGetForObject()
    {
        $this->logInWithPermission('ADMIN');
        $object = new ChangeSetItemTest\VersionedObject(['Foo' => 1]);
        $object->write();

        $item = new ChangeSetItem(
            [
            'ObjectID' => $object->ID,
            'ObjectClass' => $object->baseClass(),
            ]
        );
        $item->write();

        $this->assertEquals(
            ChangeSetItemTest\VersionedObject::get()->byID($object->ID)->toMap(),
            ChangeSetItem::get_for_object($object)->first()->Object()->toMap()
        );

        $this->assertEquals(
            ChangeSetItemTest\VersionedObject::get()->byID($object->ID)->toMap(),
            ChangeSetItem::get_for_object_by_id($object->ID, $object->ClassName)->first()->Object()->toMap()
        );
    }
}
