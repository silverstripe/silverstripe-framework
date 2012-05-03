<?php
/**
 * @package framework
 * @subpackage tests
 */
class CheckboxSetFieldTest extends SapphireTest {
	static $fixture_file = 'CheckboxSetFieldTest.yml';

	protected $extraDataObjects = array(
		'CheckboxSetFieldTest_Article',
		'CheckboxSetFieldTest_Tag',
	);
	
	function testSetDefaultItems() {
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
	
	function testSaveWithNothingSelected() {
		$article = $this->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithouttags');
		
		/* Create a CheckboxSetField with nothing selected */
		$field = new CheckboxSetField("Tags", "Test field", DataObject::get("CheckboxSetFieldTest_Tag")->map());
		
		/* Saving should work */
		$field->saveInto($article);
		
		$this->assertNull(
			DB::query("SELECT * 
				FROM \"CheckboxSetFieldTest_Article_Tags\"
				WHERE \"CheckboxSetFieldTest_Article_Tags\".\"CheckboxSetFieldTest_ArticleID\" = $article->ID
			")->value(),
			'Nothing should go into manymany join table for a saved field without any ticked boxes'
		);	
	}
	
	function testSaveWithArrayValueSet() {
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
			DB::query("SELECT \"CheckboxSetFieldTest_TagID\"
				FROM \"CheckboxSetFieldTest_Article_Tags\"
				WHERE \"CheckboxSetFieldTest_Article_Tags\".\"CheckboxSetFieldTest_ArticleID\" = $article->ID
			")->column(),
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
	
	function testLoadDataFromObject() {
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
			'CheckboxSetField properly loads data from a manymany relationship in an object through Form->loadDataFrom()'
		);
	}
}

class CheckboxSetFieldTest_Article extends DataObject implements TestOnly {
	static $db = array(
		"Content" => "Text",
	);
	
	static $many_many = array(
		"Tags" => "CheckboxSetFieldTest_Tag",
	);
	
}

class CheckboxSetFieldTest_Tag extends DataObject implements TestOnly {
	static $belongs_many_many = array(
		'Articles' => 'CheckboxSetFieldTest_Article'
	);
}
