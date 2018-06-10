<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\LookupField;
use SilverStripe\Security\Member;

class LookupFieldTest extends SapphireTest
{
    protected static $fixture_file = 'LookupFieldTest.yml';

    public function testNullValueWithNumericArraySource()
    {
        $source = array(1 => 'one', 2 => 'two', 3 => 'three');
        $field = new LookupField('test', 'test', $source);
        $field->setValue(null);
        $result = trim($field->Field()->getValue());

        $this->assertContains('<span class="readonly" id="test"><i>(none)</i></span>', $result);
        $this->assertContains('<input type="hidden" name="test" value="" />', $result);
    }

    public function testStringValueWithNumericArraySource()
    {
        $source = array(1 => 'one', 2 => 'two', 3 => 'three');
        $field = new LookupField('test', 'test', $source);
        $field->setValue(1);
        $result = trim($field->Field()->getValue());
        $this->assertContains('<span class="readonly" id="test">one</span>', $result);
        $this->assertContains('<input type="hidden" name="test" value="1" />', $result);
    }

    public function testUnknownStringValueWithNumericArraySource()
    {
        $source = array(1 => 'one', 2 => 'two', 3 => 'three');
        $field = new LookupField('test', 'test', $source);
        $field->setValue('w00t');
        $result = trim($field->Field()->getValue());

        $this->assertContains('<span class="readonly" id="test">w00t</span>', $result);
        $this->assertContains('<input type="hidden" name="test" value="" />', $result);
    }

    public function testArrayValueWithAssociativeArraySource()
    {
        // Array values (= multiple selections) might be set e.g. from ListboxField
        $source = array('one' => 'one val', 'two' => 'two val', 'three' => 'three val');
        $field = new LookupField('test', 'test', $source);
        $field->setValue(array('one','two'));
        $result = trim($field->Field()->getValue());

        $this->assertContains('<span class="readonly" id="test">one val, two val</span>', $result);
        $this->assertContains('<input type="hidden" name="test" value="one, two" />', $result);
    }

    public function testArrayValueWithNumericArraySource()
    {
        // Array values (= multiple selections) might be set e.g. from ListboxField
        $source = array(1 => 'one', 2 => 'two', 3 => 'three');
        $field = new LookupField('test', 'test', $source);
        $field->setValue(array(1,2));
        $result = trim($field->Field()->getValue());

        $this->assertContains('<span class="readonly" id="test">one, two</span>', $result);
        $this->assertContains('<input type="hidden" name="test" value="1, 2" />', $result);
    }

    public function testArrayValueWithSqlMapSource()
    {
        $member1 = $this->objFromFixture(Member::class, 'member1');
        $member2 = $this->objFromFixture(Member::class, 'member2');
        $member3 = $this->objFromFixture(Member::class, 'member3');

        $source = DataObject::get(Member::class);
        $field = new LookupField('test', 'test', $source->map('ID', 'FirstName'));
        $field->setValue(array($member1->ID, $member2->ID));
        $result = trim($field->Field()->getValue());

        $this->assertContains('<span class="readonly" id="test">member1, member2</span>', $result);
        $this->assertContains(sprintf(
            '<input type="hidden" name="test" value="%s, %s" />',
            $member1->ID,
            $member2->ID
        ), $result);
    }

    public function testWithMultiDimensionalSource()
    {
        $choices = array(
            "Non-vegetarian" => array(
                0 => 'Carnivore',
            ),
            "Vegetarian" => array(
                3 => 'Carrots',
            ),
            "Other" => array(
                9 => 'Vegan'
            )
        );

        $field = new LookupField('test', 'test', $choices);
        $field->setValue(3);
        $result = trim($field->Field()->getValue());

        $this->assertContains('<span class="readonly" id="test">Carrots</span>', $result);
        $this->assertContains('<input type="hidden" name="test" value="3" />', $result);

        $field->setValue([3, 9]);
        $result = trim($field->Field()->getValue());

        $this->assertContains('<span class="readonly" id="test">Carrots, Vegan</span>', $result);
        $this->assertContains('<input type="hidden" name="test" value="3, 9" />', $result);
    }
}
