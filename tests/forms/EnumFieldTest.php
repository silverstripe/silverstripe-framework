<?php


use SilverStripe\ORM\FieldType\DBEnum;


/**
 * @package framework
 * @subpackage tests
 */
class EnumFieldTest extends SapphireTest {
	public function testAnyFieldIsPresentInSearchField() {
		$values = array (
				'Key' => 'Value'
		);
		$enumField = new DBEnum('testField', $values);

		$searchField = $enumField->scaffoldSearchField();

		$anyText = "(" . _t('Enum.ANY', 'Any') . ")";
		$this->assertEquals(true, $searchField->getHasEmptyDefault());
		$this->assertEquals($anyText, $searchField->getEmptyString());
	}

	public function testEnumParsing() {
		$enum = new DBEnum('testField', "
			,
			0,
			Item1,
			Item2,
			Item 3,
			Item-4,
			item 5
			still 5,
			trailing comma,
		");

		$this->assertEquals(ArrayLib::valuekey(array(
			'',
			'0',
			'Item1',
			'Item2',
			'Item 3',
		    'Item-4',
			'item 5
			still 5',
			'trailing comma'
		)), $enum->enumValues());
	}
}
