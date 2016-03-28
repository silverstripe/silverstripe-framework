<?php

class ChangeSetItemTest_Versioned extends DataObject {
	private static $db = [
		'Foo' => 'Int'
	];

	private static $extensions = [
		"Versioned"
	];

	function canEdit($member = null) { return true; }
}

/**
 * @package framework
 * @subpackage tests
 */
class ChangeSetItemTest extends SapphireTest {

	protected $extraDataObjects = [
		'ChangeSetItemTest_Versioned'
	];

	function testChangeType() {
		$object = new ChangeSetItemTest_Versioned(['Foo' => 1]);
		$object->write();

		$item = new ChangeSetItem([
			'ObjectID' => $object->ID,
			'ObjectClass' => $object->ClassName
		]);

		$this->assertEquals(
			ChangeSetItem::CHANGE_CREATED, $item->ChangeType,
			'New objects that aren\'t yet published should return created'
		);

		$object->doPublish();

		$this->assertEquals(
			ChangeSetItem::CHANGE_NONE, $item->ChangeType,
			'Objects that have just been published should return no change'
		);

		$object->Foo += 1;
		$object->write();

		$this->assertEquals(
			ChangeSetItem::CHANGE_MODIFIED, $item->ChangeType,
			'Object that have unpublished changes written to draft should show as modified'
		);

		$object->doPublish();

		$this->assertEquals(
			ChangeSetItem::CHANGE_NONE, $item->ChangeType,
			'Objects that have just been published should return no change'
		);

		// We need to use a copy, because ID is set to 0 by delete, causing the following unpublish to fail
		$objectCopy = clone $object; $objectCopy->delete();

		$this->assertEquals(
			ChangeSetItem::CHANGE_DELETED, $item->ChangeType,
			'Objects that have been deleted from draft (but not yet unpublished) should show as deleted'
		);

		$object->doUnpublish();

		$this->assertEquals(
			ChangeSetItem::CHANGE_NONE, $item->ChangeType,
			'Objects that have been deleted and then unpublished should return no change'
		);
	}
}
