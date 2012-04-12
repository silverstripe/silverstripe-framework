<?php
/**
 * @package framework
 * @subpackage tests
 */

class ListboxFieldTest extends SapphireTest {

	static $fixture_file = 'ListboxFieldTest.yml';
	
	protected $extraDataObjects = array('ListboxFieldTest_DataObject', 'ListboxFieldTest_Article', 'ListboxFieldTest_Tag');

	function testFieldWithManyManyRelationship() {
		$articleWithTags = $this->objFromFixture('ListboxFieldTest_Article', 'articlewithtags');
		$tag1 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag1');
		$tag2 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag2');
		$tag3 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag3');
		$field = new ListboxField("Tags", "Test field", DataObject::get("ListboxFieldTest_Tag")->map()->toArray());
		$field->setMultiple(true);
		$field->setValue(null, $articleWithTags);

		$p = new CSSContentParser($field->Field());
		$tag1xml = $p->getByXpath('//option[@value=' . $tag1->ID . ']');
		$tag2xml = $p->getByXpath('//option[@value=' . $tag2->ID . ']');
		$tag3xml = $p->getByXpath('//option[@value=' . $tag3->ID . ']');
		$this->assertEquals('selected', (string)$tag1xml[0]['selected']);
		$this->assertEquals('selected', (string)$tag2xml[0]['selected']);
		$this->assertNull($tag3xml[0]['selected']);
	}

	function testFieldWithDisabledItems() {
		$articleWithTags = $this->objFromFixture('ListboxFieldTest_Article', 'articlewithtags');
		$tag1 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag1');
		$tag2 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag2');
		$tag3 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag3');
		$field = new ListboxField("Tags", "Test field", DataObject::get("ListboxFieldTest_Tag")->map()->toArray());
		$field->setMultiple(true);
		$field->setValue(null, $articleWithTags);
		$field->setDisabledItems(array($tag1->ID, $tag3->ID));

		$p = new CSSContentParser($field->Field());
		$tag1xml = $p->getByXpath('//option[@value=' . $tag1->ID . ']');
		$tag2xml = $p->getByXpath('//option[@value=' . $tag2->ID . ']');
		$tag3xml = $p->getByXpath('//option[@value=' . $tag3->ID . ']');
		$this->assertEquals('selected', (string)$tag1xml[0]['selected']);
		$this->assertEquals('disabled', (string)$tag1xml[0]['disabled']);
		$this->assertEquals('selected', (string)$tag2xml[0]['selected']);
		$this->assertNull($tag2xml[0]['disabled']);
		$this->assertNull($tag3xml[0]['selected']);
		$this->assertEquals('disabled', (string)$tag3xml[0]['disabled']);
	}
	
	function testSaveIntoNullValueWithMultipleOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('a');
		$field->saveInto($obj);
		$field->setValue(null);
		$field->saveInto($obj);
		$this->assertNull($obj->Choices);
	}
	
	function testSaveIntoNullValueWithMultipleOn() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('a,c');
		$field->saveInto($obj);
		$field->setValue('');
		$field->saveInto($obj);
		$this->assertEquals('', $obj->Choices);
	}
	
	function testSaveInto() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = false;
		
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('a');
		$field->saveInto($obj);
		$this->assertEquals('a', $obj->Choices);
	}
	
	function testSaveIntoMultiple() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		
		// As array
		$obj1 = new ListboxFieldTest_DataObject();
		$field->setValue(array('a', 'c'));
		$field->saveInto($obj1);
		$this->assertEquals('a,c', $obj1->Choices);
		
		// As string
		$obj2 = new ListboxFieldTest_DataObject();
		$field->setValue('a,c');
		$field->saveInto($obj2);
		$this->assertEquals('a,c', $obj2->Choices);
	}

	function testSaveIntoManyManyRelation() {
		$article = $this->objFromFixture('ListboxFieldTest_Article', 'articlewithouttags');
		$articleWithTags = $this->objFromFixture('ListboxFieldTest_Article', 'articlewithtags');
		$tag1 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag1');
		$tag2 = $this->objFromFixture('ListboxFieldTest_Tag', 'tag2');
		$field = new ListboxField("Tags", "Test field", DataObject::get("ListboxFieldTest_Tag")->map()->toArray());
		$field->setMultiple(true);

		// Save new relations
		$field->setValue(array($tag1->ID,$tag2->ID));
		$field->saveInto($article);
		$article = Dataobject::get_by_id('ListboxFieldTest_Article', $article->ID, false);
		$this->assertEquals(array($tag1->ID, $tag2->ID), $article->Tags()->sort('ID')->column('ID'));

		// Remove existing relation
		$field->setValue(array($tag1->ID));
		$field->saveInto($article);
		$article = Dataobject::get_by_id('ListboxFieldTest_Article', $article->ID, false);
		$this->assertEquals(array($tag1->ID), $article->Tags()->sort('ID')->column('ID'));

		// Set NULL value
		$field->setValue(null);
		$field->saveInto($article);
		$article = Dataobject::get_by_id('ListboxFieldTest_Article', $article->ID, false);
		$this->assertEquals(array(), $article->Tags()->sort('ID')->column('ID'));
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testSetValueFailsOnArrayIfMultipleIsOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = false;
		
		// As array (type error)
		$failsOnArray = false;
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue(array('a', 'c'));
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testSetValueFailsOnStringIfChoiceInvalidAndMultipleIsOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = false;
		
		// As string (invalid choice as comma is regarded literal)
		$obj = new ListboxFieldTest_DataObject();
		$field->setValue('invalid');
	}
	
	function testFieldRenderingMultipleOff() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		$field->setValue('a');
		$parser = new CSSContentParser($field->Field());
		$optEls = $parser->getBySelector('option');
		$this->assertEquals(3, count($optEls));
		$this->assertEquals('selected', (string)$optEls[0]['selected']);
		$this->assertEquals('', (string)$optEls[1]['selected']);
		$this->assertEquals('', (string)$optEls[2]['selected']);
	}
	
	function testFieldRenderingMultipleOn() {
		$choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
		$field = new ListboxField('Choices', 'Choices', $choices);
		$field->multiple = true;
		$field->setValue('a,c');
		$parser = new CSSContentParser($field->Field());
		$optEls = $parser->getBySelector('option');
		$this->assertEquals(3, count($optEls));
		$this->assertEquals('selected', (string)$optEls[0]['selected']);
		$this->assertEquals('', (string)$optEls[1]['selected']);
		$this->assertEquals('selected', (string)$optEls[2]['selected']);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testCommasInSourceKeys() {
		$choices = array('a' => 'a value', 'b,with,comma' => 'b value,with,comma',);
		$field = new ListboxField('Choices', 'Choices', $choices);
	}
	
}

class ListboxFieldTest_DataObject extends DataObject implements TestOnly {
	static $db = array(
		'Choices' => 'Text'
	);
}

class ListboxFieldTest_Article extends DataObject implements TestOnly {
	static $db = array(
		"Content" => "Text",
	);
	
	static $many_many = array(
		"Tags" => "ListboxFieldTest_Tag",
	);
	
}

class ListboxFieldTest_Tag extends DataObject implements TestOnly {
	static $belongs_many_many = array(
		'Articles' => 'ListboxFieldTest_Article'
	);
}
