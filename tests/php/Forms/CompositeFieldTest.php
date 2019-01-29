<?php

namespace SilverStripe\Forms\Tests;

use PHPUnit_Framework_Error;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;

class CompositeFieldTest extends SapphireTest
{

    public function testFieldPosition()
    {
        $compositeOuter = new CompositeField(
            new TextField('A'),
            new TextField('B'),
            $compositeInner = new CompositeField(
                new TextField('C1'),
                new TextField('C2')
            ),
            new TextField('D')
        );

        $this->assertEquals(0, $compositeOuter->fieldPosition('A'));
        $this->assertEquals(1, $compositeOuter->fieldPosition('B'));
        $this->assertEquals(3, $compositeOuter->fieldPosition('D'));

        $this->assertEquals(0, $compositeInner->fieldPosition('C1'));
        $this->assertEquals(1, $compositeInner->fieldPosition('C2'));

        $compositeOuter->insertBefore('B', new TextField('AB'));
        $this->assertEquals(0, $compositeOuter->fieldPosition('A'));
        $this->assertEquals(1, $compositeOuter->fieldPosition('AB'));
        $this->assertEquals(2, $compositeOuter->fieldPosition('B'));

        $this->assertFalse($compositeOuter->fieldPosition(null), 'Falsy input should return false');
        $this->assertFalse($compositeOuter->fieldPosition('FOO'), 'Non-exitent child should return false');
    }

    public function testTag()
    {
        $div = new CompositeField(
            new TextField('A'),
            new TextField('B')
        );
        $this->assertStringStartsWith('<div', trim($div->FieldHolder()));
        $this->assertStringEndsWith('/div>', trim($div->FieldHolder()));

        $fieldset = new CompositeField();
        $fieldset->setTag('fieldset');

        $this->assertStringStartsWith('<fieldset', trim($fieldset->FieldHolder()));
        $this->assertStringEndsWith('/fieldset>', trim($fieldset->FieldHolder()));
    }

    public function testPushAndUnshift()
    {
        $composite = new CompositeField(
            new TextField('Middle')
        );

        $composite->push(new TextField('End'));
        /* There are now 2 fields in the set */
        $this->assertEquals(2, $composite->getChildren()->Count());
        /* The most recently added field is at the end of the set */
        $this->assertEquals('End', $composite->getChildren()->Last()->getName());

        $composite->unshift(new TextField('Beginning'));
        /* There are now 3 fields in the set */
        $this->assertEquals(3, $composite->getChildren()->Count());
        /* The most recently added field is at the beginning of the set */
        $this->assertEquals('Beginning', $composite->getChildren()->First()->getName());
    }

    public function testLegend()
    {
        $composite = new CompositeField(
            new TextField('A'),
            new TextField('B')
        );

        $composite->setTag('fieldset');
        $composite->setLegend('My legend');

        $parser = new CSSContentParser($composite->FieldHolder());
        $root = $parser->getBySelector('fieldset.composite');
        $legend = $parser->getBySelector('fieldset.composite legend');

        $this->assertNotNull($legend);
        $this->assertEquals('My legend', (string)$legend[0]);
    }

    public function testValidation()
    {
        $field = CompositeField::create(
            $fieldOne = DropdownField::create('A', '', [ 'value' => 'value' ]),
            $fieldTwo = TextField::create('B')
        );
        $validator = new RequiredFields();
        $this->assertFalse(
            $field->validate($validator),
            "Validation fails when child is invalid"
        );
        $fieldOne->setEmptyString('empty');
        $this->assertTrue(
            $field->validate($validator),
            "Validates when children are valid"
        );
    }

    public function testChildren()
    {
        $field = CompositeField::create();

        $this->assertInstanceOf(FieldList::class, $field->getChildren());
        $this->assertEquals($field, $field->getChildren()->getContainerField());

        $expectedChildren = FieldList::create(
            $fieldOne = DropdownField::create('A', '', [ 'value' => 'value' ]),
            $fieldTwo = TextField::create('B')
        );
        $field->setChildren($expectedChildren);
        $this->assertEquals($expectedChildren, $field->getChildren());
        $this->assertEquals($field, $expectedChildren->getContainerField());
    }

    public function testExtraClass()
    {
        $field = CompositeField::create();
        $field->setColumnCount(3);
        $result = $field->extraClass();

        $this->assertContains('field', $result, 'Default class was not added');
        $this->assertContains('CompositeField', $result, 'Default class was not added');
        $this->assertContains('multicolumn', $result, 'Multi column field did not have extra class added');
    }

    public function testGetAttributes()
    {
        $field = CompositeField::create();
        $field->setLegend('test');
        $result = $field->getAttributes();

        $this->assertNull($result['tabindex']);
        $this->assertNull($result['type']);
        $this->assertNull($result['value']);
        $this->assertSame('test', $result['title']);
    }

    public function testGetAttributesReturnsEmptyTitleForFieldSets()
    {
        $field = CompositeField::create();
        $field->setLegend('not used');
        $field->setTag('fieldset');
        $result = $field->getAttributes();
        $this->assertNull($result['title']);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessageRegExp /a field called 'Test' appears twice in your form.*TextField.*TextField/
     */
    public function testCollateDataFieldsThrowsErrorOnDuplicateChildren()
    {
        $field = CompositeField::create(
            TextField::create('Test'),
            TextField::create('Test')
        );

        $list = [];
        $field->collateDataFields($list);
    }

    public function testCollateDataFieldsWithSaveableOnly()
    {
        $field = CompositeField::create(
            TextField::create('Test')
                ->setReadonly(false)
                ->setDisabled(true)
        );

        $list = [];
        $field->collateDataFields($list, true);
        $this->assertEmpty($list, 'Unsaveable fields should not be collated when $saveableOnly = true');

        $field->collateDataFields($list, false);
        $this->assertNotEmpty($list, 'Unsavable fields should be collated when $saveableOnly = false');
    }

    public function testSetDisabledPropagatesToChildren()
    {
        $field = CompositeField::create(
            $testField = TextField::create('Test')
                ->setDisabled(false)
        )->setDisabled(true);
        $this->assertTrue($field->fieldByName('Test')->isDisabled(), 'Children should also be set to disabled');
    }

    public function testIsComposite()
    {
        $this->assertTrue(CompositeField::create()->isComposite());
    }

    public function testMakeFieldReadonlyPassedFieldName()
    {
        $field = CompositeField::create(
            TextField::create('Test')->setDisabled(false)
        );

        $this->assertFalse($field->fieldByName('Test')->isReadonly());
        $this->assertTrue($field->makeFieldReadonly('Test'), 'makeFieldReadonly should return true');
        $this->assertTrue($field->fieldByName('Test')->isReadonly(), 'Named child field should be made readonly');
    }

    public function testMakeFieldReadonlyPassedFormField()
    {
        $field = CompositeField::create(
            $testField = TextField::create('Test')->setDisabled(false)
        );

        $this->assertFalse($field->fieldByName('Test')->isReadonly());
        $this->assertTrue($field->makeFieldReadonly($testField), 'makeFieldReadonly should return true');
        $this->assertTrue($field->fieldByName('Test')->isReadonly(), 'Named child field should be made readonly');
    }

    public function testMakeFieldReadonlyWithNestedCompositeFields()
    {
        $field = CompositeField::create(
            CompositeField::create(
                TextField::create('Test')->setDisabled(false)
            )
        );

        $this->assertFalse($field->getChildren()->first()->fieldByName('Test')->isReadonly());
        $this->assertTrue($field->makeFieldReadonly('Test'), 'makeFieldReadonly should return true');
        $this->assertTrue(
            $field->getChildren()->first()->fieldByName('Test')->isReadonly(),
            'Named child field should be made readonly'
        );
    }

    public function testMakeFieldReadonlyReturnsFalseWhenFieldNotFound()
    {
        $field = CompositeField::create(
            CompositeField::create(
                TextField::create('Test')
            )
        );

        $this->assertFalse(
            $field->makeFieldReadonly('NonExistent'),
            'makeFieldReadonly should return false when field is not found'
        );
    }

    public function testDebug()
    {
        $field = new CompositeField(
            new TextField('TestTextField')
        );
        $field->setName('TestComposite');

        $result = $field->debug();
        $this->assertContains(CompositeField::class . ' (TestComposite)', $result);
        $this->assertContains('TestTextField', $result);
        $this->assertContains('<ul', $result, 'Result should be formatted as a <ul>');
    }
}
