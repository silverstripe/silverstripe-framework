<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDecimal;
use TypeError;

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
        $x=1;
    }

    public function testDefaultValue()
    {
        $this->assertSame(
            0.0,
            $this->testDataObject->MyDecimal1,
            'Database default for Decimal type is 0.0'
        );
    }

    public function testSpecifiedDefaultValue()
    {
        $this->assertSame(
            2.5,
            $this->testDataObject->MyDecimal2,
            'Default value for Decimal type is set to 2.5'
        );
    }

    public function testInvalidSpecifiedDefaultValue()
    {
        $this->expectException(TypeError::class);
        new DBDecimal(defaultValue: 'Invalid');
    }

    public function testSpecifiedDefaultValueInDefaultsArray()
    {
        $this->assertSame(
            $this->testDataObject->MyDecimal4,
            4.0,
            'Default value for Decimal type is set to 4'
        );
    }

    public function testLongValueStoredCorrectly()
    {
        $this->assertSame(
            1.0,
            $this->testDataObject->MyDecimal5,
            'Long default long decimal value is rounded correctly'
        );

        $this->assertEqualsWithDelta(
            0.99999999999999999999,
            $this->testDataObject->MyDecimal5,
            PHP_FLOAT_EPSILON,
            'Long default long decimal value is correct within float epsilon'
        );

        $this->assertSame(
            8.0,
            $this->testDataObject->MyDecimal6,
            'Long decimal value with a default value is rounded correctly'
        );

        $this->assertEqualsWithDelta(
            7.99999999999999999999,
            $this->testDataObject->MyDecimal6,
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
