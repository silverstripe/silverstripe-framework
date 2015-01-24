<?php

/**
 * @package framework
 * @subpackage tests
 */
class CheckboxSetFieldTest extends SapphireTest {

	protected static $fixture_file = 'CheckboxSetFieldTest.yml';

	protected $extraDataObjects = array(
		'CheckboxSetFieldTest_Article',
		'CheckboxSetFieldTest_Tag',
	);

	public function testSetDefaultItems() {
		$f = new CheckboxSetField(
			'Test',
			false,
			array(0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three')
		);

		$f->setValue(array(0,1));
		$f->setDefaultItems(array(2));
		$p = new CSSContentParser($f->Field());
		$item0 = $p->getBySelector('#Test_0');
		$item1 = $p->getBySelector('#Test_1');
		$item2 = $p->getBySelector('#Test_2');
		$item3 = $p->getBySelector('#Test_3');
		$this->assertEquals(
			(string)$item0[0]['checked'],
			'checked',
			'Selected through value'
		);
		$this->assertEquals(
			(string)$item1[0]['checked'],
			'checked',
			'Selected through value'
		);
		$this->assertEquals(
			(string)$item2[0]['checked'],
			'checked',
			'Selected through default items'
		);
		$this->assertEquals(
			(string)$item3[0]['checked'],
			'',
			'Not selected by either value or default items'
		);
	}

	public function testSaveWithNothingSelected() {
		$article = $this->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithouttags');

		/* Create a CheckboxSetField with nothing selected */
		$field = new CheckboxSetField("Tags", "Test field", DataObject::get("CheckboxSetFieldTest_Tag")->map());

		/* Saving should work */
		$field->saveInto($article);

		$this->assertNull(
			DB::prepared_query("SELECT *
				FROM \"CheckboxSetFieldTest_Article_Tags\"
				WHERE \"CheckboxSetFieldTest_Article_Tags\".\"CheckboxSetFieldTest_ArticleID\" = ?", array($article->ID)
			)->value(),
			'Nothing should go into manymany join table for a saved field without any ticked boxes'
		);
	}

	public function testSaveWithArrayValueSet() {
		$article = $this->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithouttags');
		$articleWithTags = $this->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithtags');
		$tag1 = $this->objFromFixture('CheckboxSetFieldTest_Tag', 'tag1');
		$tag2 = $this->objFromFixture('CheckboxSetFieldTest_Tag', 'tag2');

		/* Create a CheckboxSetField with 2 items selected.  Note that the array is in the format (key) => (selected) */
		$field = new CheckboxSetField("Tags", "Test field", DataObject::get("CheckboxSetFieldTest_Tag")->map());
		$field->setValue(array(
			$tag1->ID => true,
			$tag2->ID => true
		));

		/* Saving should work */
		$field->saveInto($article);

		$this->assertEquals(
			array($tag1->ID,$tag2->ID),
			DB::prepared_query("SELECT \"CheckboxSetFieldTest_TagID\"
				FROM \"CheckboxSetFieldTest_Article_Tags\"
				WHERE \"CheckboxSetFieldTest_Article_Tags\".\"CheckboxSetFieldTest_ArticleID\" = ?", array($article->ID)
			)->column(),
			'Data shold be saved into CheckboxSetField manymany relation table on the "right end"'
		);
		$this->assertEquals(
			array($articleWithTags->ID,$article->ID),
			DB::query("SELECT \"CheckboxSetFieldTest_ArticleID\"
				FROM \"CheckboxSetFieldTest_Article_Tags\"
				WHERE \"CheckboxSetFieldTest_Article_Tags\".\"CheckboxSetFieldTest_TagID\" = $tag1->ID
			")->column(),
			'Data shold be saved into CheckboxSetField manymany relation table on the "left end"'
		);
	}

	public function testLoadDataFromObject() {
		$article = $this->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithouttags');
		$articleWithTags = $this->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithtags');
		$tag1 = $this->objFromFixture('CheckboxSetFieldTest_Tag', 'tag1');
		$tag2 = $this->objFromFixture('CheckboxSetFieldTest_Tag', 'tag2');

		$field = new CheckboxSetField("Tags", "Test field", DataObject::get("CheckboxSetFieldTest_Tag")->map());
		$form = new Form(
			new Controller(),
			'Form',
			new FieldList($field),
			new FieldList()
		);
		$form->loadDataFrom($articleWithTags);
		$this->assertEquals(
			array(
				$tag1->ID => $tag1->ID,
				$tag2->ID => $tag2->ID
			),
			$field->Value(),
			'CheckboxSetField loads data from a manymany relationship in an object through Form->loadDataFrom()'
		);
	}

	public function testSavingIntoTextField() {
		$field = new CheckboxSetField('Content', 'Content', array(
			'Test' => 'Test',
			'Another' => 'Another',
			'Something' => 'Something'
		));
		$article = new CheckboxSetFieldTest_Article();
		$field->setValue(array('Test' => 'Test', 'Another' => 'Another'));
		$field->saveInto($article);
		$article->write();

		$dbValue = DB::query(sprintf(
			'SELECT "Content" FROM "CheckboxSetFieldTest_Article" WHERE "ID" = %s',
			$article->ID
		))->value();

		$this->assertEquals('Test,Another', $dbValue);
	}

	public function testValidationWithArray() {
		//test with array input
		$field = CheckboxSetField::create('Test', 'Testing', array(
			"One" => "One",
			"Two" => "Two",
			"Three" => "Three"
		));
		$validator = new RequiredFields();
		$field->setValue(array("One" => "One", "Two" => "Two"));
		$this->assertTrue(
			$field->validate($validator),
			'Field validates values within source array'
		);
		//non valid value should fail
		$field->setValue(array("Four" => "Four"));
		$this->assertFalse(
			$field->validate($validator),
			'Field does not validate values outside of source array'
		);
		//non valid value included with valid options should succeed
		$field->setValue(array("One" => "One", "Two" => "Two", "Four" => "Four"));
		$this->assertTrue(
			$field->validate($validator),
			'Field validates when presented with mixed valid and invalid values'
		);
	}

	public function testValidationWithDataList() {
		//test with datalist input
		$checkboxTestArticle = $this->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithtags');
		$tag1 = $this->objFromFixture('CheckboxSetFieldTest_Tag', 'tag1');
		$tag2 = $this->objFromFixture('CheckboxSetFieldTest_Tag', 'tag2');
		$tag3 = $this->objFromFixture('CheckboxSetFieldTest_Tag', 'tag3');
		$field = CheckboxSetField::create('Test', 'Testing', $checkboxTestArticle->Tags()	->map());
		$validator = new RequiredFields();
		$field->setValue(array(
			$tag1->ID => $tag1->ID,
			$tag2->ID => $tag2->ID
		));
		$this->assertTrue(
			$field->validate($validator),
			'Validates values in source map'
		);
		//invalid value should fail
		$fakeID = CheckboxSetFieldTest_Tag::get()->max('ID') + 1;
		$field->setValue(array($fakeID => $fakeID));
		$this->assertFalse(
			$field->validate($validator),
			'Field does not valid values outside of source map'
		);
		//non valid value included with valid options should succeed
		$field->setValue(array(
			$tag1->ID => $tag1->ID,
			$tag2->ID => $tag2->ID,
			$tag3->ID => $tag3->ID
		));
		$this->assertTrue(
			$field->validate($validator),
			'Validates when presented with mixed valid and invalid values'
		);
	}

}

/**
 * @package framework
 * @subpackage tests
 */

class CheckboxSetFieldTest_Article extends DataObject implements TestOnly {

	private static $db = array(
		"Content" => "Text",
	);

	private static $many_many = array(
		"Tags" => "CheckboxSetFieldTest_Tag",
	);

}

/**
 * @package framework
 * @subpackage tests
 */
class CheckboxSetFieldTest_Tag extends DataObject implements TestOnly {

	private static $belongs_many_many = array(
		'Articles' => 'CheckboxSetFieldTest_Article'
	);
}
