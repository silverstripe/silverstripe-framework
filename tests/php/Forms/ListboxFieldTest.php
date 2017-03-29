<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\Tests\ListboxFieldTest\Article;
use SilverStripe\Forms\Tests\ListboxFieldTest\Tag;
use SilverStripe\Forms\Tests\ListboxFieldTest\TestObject;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\View\ArrayData;

class ListboxFieldTest extends SapphireTest
{

    protected static $fixture_file = 'ListboxFieldTest.yml';

    protected static $extra_dataobjects = array(
        TestObject::class,
        Article::class,
        Tag::class,
    );

    public function testFieldWithManyManyRelationship()
    {
        $articleWithTags = $this->objFromFixture(Article::class, 'articlewithtags');
        $tag1 = $this->objFromFixture(Tag::class, 'tag1');
        $tag2 = $this->objFromFixture(Tag::class, 'tag2');
        $tag3 = $this->objFromFixture(Tag::class, 'tag3');
        $field = new ListboxField("Tags", "Test field", DataObject::get(Tag::class)->map()->toArray());
        $field->setValue(null, $articleWithTags);

        $p = new CSSContentParser($field->Field());
        $tag1xml = $p->getByXpath('//option[@value=' . $tag1->ID . ']');
        $tag2xml = $p->getByXpath('//option[@value=' . $tag2->ID . ']');
        $tag3xml = $p->getByXpath('//option[@value=' . $tag3->ID . ']');
        $this->assertEquals('selected', (string)$tag1xml[0]['selected']);
        $this->assertEquals('selected', (string)$tag2xml[0]['selected']);
        $this->assertNull($tag3xml[0]['selected']);
    }

    public function testFieldWithDisabledItems()
    {
        $articleWithTags = $this->objFromFixture(Article::class, 'articlewithtags');
        $tag1 = $this->objFromFixture(Tag::class, 'tag1');
        $tag2 = $this->objFromFixture(Tag::class, 'tag2');
        $tag3 = $this->objFromFixture(Tag::class, 'tag3');
        $field = new ListboxField("Tags", "Test field", DataObject::get(Tag::class)->map()->toArray());
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

    public function testSaveIntoNullValueWithMultipleOff()
    {
        $choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
        $field = new ListboxField('Choices', 'Choices', $choices);

        $obj = new TestObject();
        $field->setValue('a');
        $field->saveInto($obj);
        $field->setValue(null);
        $field->saveInto($obj);
        $this->assertNull($obj->Choices);
    }

    public function testSaveIntoNullValueWithMultipleOn()
    {
        $choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
        $field = new ListboxField('Choices', 'Choices', $choices);

        $obj = new TestObject();
        $field->setValue(array('a', 'c'));
        $field->saveInto($obj);
        $field->setValue('');
        $field->saveInto($obj);
        $this->assertEquals('', $obj->Choices);
    }

    public function testSaveInto()
    {
        $choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
        $field = new ListboxField('Choices', 'Choices', $choices);

        $obj = new TestObject();
        $field->setValue('a');
        $field->saveInto($obj);
        $this->assertEquals('["a"]', $obj->Choices);
    }

    public function testSaveIntoMultiple()
    {
        $choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
        $field = new ListboxField('Choices', 'Choices', $choices);

        // As array
        $obj1 = new TestObject();
        $field->setValue(array('a', 'c'));
        $field->saveInto($obj1);
        $this->assertEquals('["a","c"]', $obj1->Choices);

        // As string
        $obj2 = new TestObject();
        $obj2->Choices = '["a","c"]';
        $field->setValue(null, $obj2);
        $this->assertEquals(array('a', 'c'), $field->Value());
        $field->saveInto($obj2);
        $this->assertEquals('["a","c"]', $obj2->Choices);
    }

    public function testSaveIntoManyManyRelation()
    {
        $article = $this->objFromFixture(Article::class, 'articlewithouttags');
        $articleWithTags = $this->objFromFixture(Article::class, 'articlewithtags');
        $tag1 = $this->objFromFixture(Tag::class, 'tag1');
        $tag2 = $this->objFromFixture(Tag::class, 'tag2');
        $field = new ListboxField("Tags", "Test field", DataObject::get(Tag::class)->map()->toArray());

        // Save new relations
        $field->setValue(array($tag1->ID,$tag2->ID));
        $field->saveInto($article);
        $article = DataObject::get_by_id(Article::class, $article->ID, false);
        $this->assertEquals(array($tag1->ID, $tag2->ID), $article->Tags()->sort('ID')->column('ID'));

        // Remove existing relation
        $field->setValue(array($tag1->ID));
        $field->saveInto($article);
        $article = DataObject::get_by_id(Article::class, $article->ID, false);
        $this->assertEquals(array($tag1->ID), $article->Tags()->sort('ID')->column('ID'));

        // Set NULL value
        $field->setValue(null);
        $field->saveInto($article);
        $article = DataObject::get_by_id(Article::class, $article->ID, false);
        $this->assertEquals(array(), $article->Tags()->sort('ID')->column('ID'));
    }

    public function testFieldRenderingMultipleOff()
    {
        $choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
        $field = new ListboxField('Choices', 'Choices', $choices);
        $field->setValue('a');
        $parser = new CSSContentParser($field->Field());
        $optEls = $parser->getBySelector('option');
        $this->assertEquals(3, count($optEls));
        $this->assertEquals('selected', (string)$optEls[0]['selected']);
        $this->assertEquals('', (string)$optEls[1]['selected']);
        $this->assertEquals('', (string)$optEls[2]['selected']);
    }

    public function testFieldRenderingMultipleOn()
    {
        $choices = array('a' => 'a value', 'b' => 'b value','c' => 'c value');
        $field = new ListboxField('Choices', 'Choices', $choices);
        $field->setValue(array('a', 'c'));
        $parser = new CSSContentParser($field->Field());
        $optEls = $parser->getBySelector('option');
        $this->assertEquals(3, count($optEls));
        $this->assertEquals('selected', (string)$optEls[0]['selected']);
        $this->assertEquals('', (string)$optEls[1]['selected']);
        $this->assertEquals('selected', (string)$optEls[2]['selected']);
    }

    public function testValidationWithArray()
    {
        //test with array input
        $field = ListboxField::create(
            'Test',
            'Testing',
            array(
            1 => "One",
            2 => "Two",
            3 => "Three"
            )
        );
        $validator = new RequiredFields();

        $field->setValue(1);
        $this->assertTrue(
            $field->validate($validator),
            'Validates values in source map'
        );
        $field->setValue(array(1));
        $this->assertTrue(
            $field->validate($validator),
            'Validates values within source array'
        );
        //non valid value should fail
        $field->setValue(4);
        $this->assertFalse(
            $field->validate($validator),
            'Does not validates values not within source array'
        );
    }

    public function testValidationWithDataList()
    {
        //test with datalist input
        $tag1 = $this->objFromFixture(Tag::class, 'tag1');
        $tag2 = $this->objFromFixture(Tag::class, 'tag2');
        $tag3 = $this->objFromFixture(Tag::class, 'tag3');
        $field = ListboxField::create('Test', 'Testing', DataObject::get(Tag::class)->map()->toArray());
        $validator = new RequiredFields();

        $field->setValue(
            $tag1->ID
        );
        $this->assertTrue(
            $field->validate($validator),
            'Field validates values in source map'
        );

        /**
         * @todo re-enable these tests when field validation is removed from {@link ListboxField::setValue()} and moved
         * to the {@link ListboxField::validate()} function
         */
        //		$field->setValue(4);
        //		$this->assertFalse(
        //			$field->validate($validator),
        //			'Field does not validate values outside of source map'
        //		);
        $field->setValue(
            false,
            new ArrayData(
                array(
                $tag1->ID => $tag1->ID,
                $tag2->ID => $tag2->ID
                )
            )
        );
        $this->assertTrue(
            $field->validate($validator),
            'Validates values in source map'
        );
        //invalid value should fail
        $field->setValue(4);
        $this->assertFalse(
            $field->validate($validator),
            'Does not validate values not within source map'
        );
    }
}
