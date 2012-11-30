<?php
class SelectionGroupTest extends SapphireTest {

	function testFieldHolder() {
		$items = array(
			new SelectionGroup_Item(
				'one',
				new LiteralField('one', 'one view'),
				'one title'
			),
			new SelectionGroup_Item(
				'two',
				new LiteralField('two', 'two view'),
				'two title'
			),
		);
		$field = new SelectionGroup('MyGroup', $items);
		$parser = new CSSContentParser($field->FieldHolder());
		$listEls = $parser->getBySelector('li');
		$listElOne = $listEls[0];
		$listElTwo = $listEls[1];

		$this->assertEquals('one', (string)$listElOne->input[0]['value']);
		$this->assertEquals('two', (string)$listElTwo->input[0]['value']);

		$this->assertEquals('one title', $listElOne->label[0]);
		$this->assertEquals('two title', $listElTwo->label[0]);

		$this->assertContains('one view', (string)$listElOne);
		$this->assertContains('two view', (string)$listElTwo);
	}
	
	function testLegacyItemsFieldHolder() {
		$items = array(
			'one' => new LiteralField('one', 'one view'),
			'two' => new LiteralField('two', 'two view'),
		);
		$field = new SelectionGroup('MyGroup', $items);
		$parser = new CSSContentParser($field->FieldHolder());
		$listEls = $parser->getBySelector('li');
		$listElOne = $listEls[0];
		$listElTwo = $listEls[1];

		$this->assertEquals('one', (string)$listElOne->input[0]['value']);
		$this->assertEquals('two', (string)$listElTwo->input[0]['value']);

		$this->assertEquals('one', $listElOne->label[0]);
		$this->assertEquals('two', $listElTwo->label[0]);
	}

	function testLegacyItemsFieldHolderWithTitle() {
		$items = array(
			'one//one title' => new LiteralField('one', 'one view'),
			'two//two title' => new LiteralField('two', 'two view'),
		);
		$field = new SelectionGroup('MyGroup', $items);
		$parser = new CSSContentParser($field->FieldHolder());
		$listEls = $parser->getBySelector('li');
		$listElOne = $listEls[0];
		$listElTwo = $listEls[1];

		$this->assertEquals('one', (string)$listElOne->input[0]['value']);
		$this->assertEquals('two', (string)$listElTwo->input[0]['value']);

		$this->assertEquals('one title', $listElOne->label[0]);
		$this->assertEquals('two title', $listElTwo->label[0]);
	}

}