<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDecimal;

class DecimalTest extends SapphireTest
{

    protected static $fixture_file = 'DecimalTest.yml';

    /**
     * @var DecimalTest\TestObject
     */
    protected $testDataObject;

    protected static $extra_dataobjects = [
        DecimalTest\TestObject::class
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDataObject = $this->objFromFixture(DecimalTest\TestObject::class, 'test-dataobject');
    }

    public function testDefaultValue()
    {
        $this->assertEquals(
            $this->testDataObject->MyDecimal1,
            0,
            'Database default for Decimal type is 0'
        );
    }

    public function testSpecifiedDefaultValue()
    {
        $this->assertEquals(
            $this->testDataObject->MyDecimal2,
            2.5,
            'Default value for Decimal type is set to 2.5'
        );
    }

    public function testInvalidSpecifiedDefaultValue()
    {
        $this->assertEquals(
            $this->testDataObject->MyDecimal3,
            0,
            'Invalid default value for Decimal type is casted to 0'
        );
    }

    public function testSpecifiedDefaultValueInDefaultsArray()
    {
        $this->assertEquals(
            $this->testDataObject->MyDecimal4,
            4,
            'Default value for Decimal type is set to 4'
        );
    }

    public function testLongValueStoredCorrectly()
    {
        $this->assertEquals(
            $this->testDataObject->MyDecimal5,
            1.0,
            'Long default long decimal value is rounded correctly'
        );

        $this->assertEqualsWithDelta(
            $this->testDataObject->MyDecimal5,
            0.99999999999999999999,
            PHP_FLOAT_EPSILON,
            'Long default long decimal value is correct within float epsilon'
        );

        $this->assertEquals(
            $this->testDataObject->MyDecimal6,
            8.0,
            'Long decimal value with a default value is rounded correctly'
        );

        $this->assertEqualsWithDelta(
            $this->testDataObject->MyDecimal6,
            7.99999999999999999999,
            PHP_FLOAT_EPSILON,
            'Long decimal value is within epsilon if longer than allowed number of float digits'
        );
    }

    public function testScaffoldFormField()
    {
        /** @var DBDecimal $decimal */
        $decimal = $this->testDataObject->dbObject('MyDecimal2');
        $field = $decimal->scaffoldFormField('The Decimal');
        $this->assertEquals(3, $field->getScale());
        $field->setValue(1.9999);
        $this->assertEquals(1.9999, $field->dataValue());
        $this->assertEquals('2.000', $field->Value());
    }
}
