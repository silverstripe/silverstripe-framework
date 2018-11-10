<?php

namespace SilverStripe\Forms;

use PHPUnit_Framework_MockObject_MockObject;
use SilverStripe\Dev\SapphireTest;

class DatalessFieldTest extends SapphireTest
{
    public function testGetAttributes()
    {
        $field = new DatalessField('Name');
        $result = $field->getAttributes();
        $this->assertSame('hidden', $result['type']);
    }

    public function testFieldHolderAndSmallFieldHolderReturnField()
    {
        /** @var DatalessField|PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(DatalessField::class)
            ->disableOriginalConstructor()
            ->setMethods(['Field'])
            ->getMock();

        $properties = [
            'foo' => 'bar',
        ];

        $mock->expects($this->exactly(2))->method('Field')->with($properties)->willReturn('boo!');

        $fieldHolder = $mock->FieldHolder($properties);
        $smallFieldHolder = $mock->SmallFieldHolder($properties);

        $this->assertSame('boo!', $fieldHolder);
        $this->assertSame('boo!', $smallFieldHolder);
    }

    public function testPerformReadonlyTransformation()
    {
        $field = new DatalessField('Test');
        $result = $field->performReadonlyTransformation();
        $this->assertInstanceOf(DatalessField::class, $result);
        $this->assertTrue($result->isReadonly());
    }
}
