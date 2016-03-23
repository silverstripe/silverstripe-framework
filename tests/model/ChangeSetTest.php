<?php

class ChangeSetTest_Base extends DataObject {
	private static $db = [
		'Foo' => 'Int'
	];

	private static $has_many = [
		'Mids' => 'ChangeSetTest_Mid'
	];

	private static $owns = [
		'Mids'
	];

	private static $extensions = [
		"Versioned"
	];

	function canEdit($member = null) { return true; }
}

class ChangeSetTest_Mid extends DataObject {
	private static $db = [
		'Bar' => 'Int'
	];

	private static $has_one = [
		'Base' => 'ChangeSetTest_Base',
		'End' => 'ChangeSetTest_End'
	];

	private static $owns = [
		'End'
	];

	private static $extensions = [
		"Versioned"
	];

	function canEdit($member = null) { return true; }
}

class ChangeSetTest_End extends DataObject {
	private static $db = [
		'Baz' => 'Int'
	];

	private static $extensions = [
		"Versioned"
	];

	function canEdit($member = null) { return true; }
}

class ChangeSetTest extends SapphireTest {

	protected static $fixture_file = 'ChangeSetTest.yml';

	protected $extraDataObjects = [
		'ChangeSetTest_Base',
		'ChangeSetTest_Mid',
		'ChangeSetTest_End'
	];

	protected function publishAllFixtures() {
		foreach($this->fixtureFactory->getFixtures() as $class => $fixtures) {
			foreach ($fixtures as $handle => $id) {
				$this->objFromFixture($class, $handle)->doPublish();
			}
		}
	}

	protected function assertChangeSetLooksLike($cs, $match) {
		$items = $cs->Changes()->toArray();

		foreach($match as $key => $mode) {
			list($class, $identifier) = explode('.', $key);
			$object = $this->objFromFixture($class, $identifier);

			foreach($items as $i => $item) {
				if ($item->ObjectClass == $object->ClassName && $item->ObjectID == $object->ID && $item->Added == $mode) {
					unset($items[$i]);
					continue 2;
				}
			}

			throw new PHPUnit_Framework_ExpectationFailedException(
				'Change set didn\'t include expected item',
				new \SebastianBergmann\Comparator\ComparisonFailure(array('Class' => $class, 'ID' => $object->ID, 'Added' => $mode), null, "$key => $mode", '')
			);
		}

		if (count($items)) {
			$extra = [];
			foreach ($items as $item) $extra[] = ['Class' => $item->ObjectClass, 'ID' => $item->ObjectID, 'Added' => $item->Added];
			throw new PHPUnit_Framework_ExpectationFailedException(
				'Change set included items that weren\'t expected',
				new \SebastianBergmann\Comparator\ComparisonFailure(array(), $extra, '', print_r($extra, true))
			);
		}
	}

	function testRepeatedSyncIsNOP() {
		$this->publishAllFixtures();

		$cs = new ChangeSet();
		$cs->write();

		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$cs->addObject($base);

		$cs->sync();
		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);

		$cs->sync();
		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);
	}

	function testSync() {
		$this->publishAllFixtures();

		$cs = new ChangeSet();
		$cs->write();

		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');

		$cs->addObject($base);
		$cs->sync();

		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);

		$end = $this->objFromFixture('ChangeSetTest_End', 'end1');
		$end->Baz = 3;
		$end->write();

		$cs->sync();

		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY,
			'ChangeSetTest_End.end1' => ChangeSetItem::IMPLICITLY
		]);

	}


	function testIsSynced() {
		$this->publishAllFixtures();

		$cs = new ChangeSet();
		$cs->write();

		$base = $this->objFromFixture('ChangeSetTest_Base', 'base');
		$cs->addObject($base);

		$cs->sync();
		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY
		]);
		$this->assertTrue($cs->isSynced());

		$end = $this->objFromFixture('ChangeSetTest_End', 'end1');
		$end->Baz = 3;
		$end->write();
		$this->assertFalse($cs->isSynced());

		$cs->sync();

		$this->assertChangeSetLooksLike($cs, [
			'ChangeSetTest_Base.base' => ChangeSetItem::EXPLICITLY,
			'ChangeSetTest_End.end1' => ChangeSetItem::IMPLICITLY
		]);
		$this->assertTrue($cs->isSynced());
	}


}