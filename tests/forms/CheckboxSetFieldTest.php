<?php

class CheckboxSetFieldTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/forms/CheckboxSetFieldTest.yml';
	
	function testSaveWithNothingSelected() {
		$article = $this->fixture->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithouttags');
		
		/* Create a CheckboxSetField with nothing selected */
		$field = new CheckboxSetField("Tags", "Test field", DataObject::get("CheckboxSetFieldTest_Tag")->map());
		
		/* Saving should work */
		$field->saveInto($article);
		
		$this->assertNull(
			DB::query("
				SELECT * 
				FROM CheckboxSetFieldTest_Article_Tags
				WHERE CheckboxSetFieldTest_Article_Tags.CheckboxSetFieldTest_ArticleID = $article->ID
			")->value(),
			'Nothing should go into manymany join table for a saved field without any ticked boxes'
		);	
	}

	function testSaveWithArrayValueSet() {
		$article = $this->fixture->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithouttags');
		$articleWithTags = $this->fixture->objFromFixture('CheckboxSetFieldTest_Article', 'articlewithtags');
		$tag1 = $this->fixture->objFromFixture('CheckboxSetFieldTest_Tag', 'tag1');
		$tag2 = $this->fixture->objFromFixture('CheckboxSetFieldTest_Tag', 'tag2');
		
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
			DB::query("
				SELECT CheckboxSetFieldTest_TagID 
				FROM CheckboxSetFieldTest_Article_Tags
				WHERE CheckboxSetFieldTest_Article_Tags.CheckboxSetFieldTest_ArticleID = $article->ID
			")->column(),
			'Data shold be saved into CheckboxSetField manymany relation table on the "right end"'
		);	
		$this->assertEquals(
			array($articleWithTags->ID,$article->ID), 
			DB::query("
				SELECT CheckboxSetFieldTest_ArticleID 
				FROM CheckboxSetFieldTest_Article_Tags
				WHERE CheckboxSetFieldTest_Article_Tags.CheckboxSetFieldTest_TagID = $tag1->ID
			")->column(),
			'Data shold be saved into CheckboxSetField manymany relation table on the "left end"'
		);	
	}
	
	function testLoadDataFrom() {
		
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