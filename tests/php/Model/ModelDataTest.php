<?php

namespace SilverStripe\Model\Tests;

use ReflectionMethod;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\Tests\ModelDataTest\ModelDataTestExtension;
use SilverStripe\Model\Tests\ModelDataTest\ModelDataTestObject;
use SilverStripe\Model\ModelData;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * See {@link SSViewerTest->testCastingHelpers()} for more tests related to casting and ModelData behaviour,
 * from a template-parsing perspective.
 */
class ModelDataTest extends SapphireTest
{
    protected static $required_extensions = [
        ModelDataTestObject::class => [
            ModelDataTestExtension::class,
        ],
    ];

    public function testCasting()
    {
        $htmlString = "&quot;";
        $textString = '"';

        $htmlField = DBField::create_field('HTMLFragment', $textString);

        $this->assertEquals($textString, $htmlField->forTemplate());
        $this->assertEquals($htmlString, $htmlField->obj('HTMLATT')->forTemplate());
        $this->assertEquals('%22', $htmlField->obj('URLATT')->forTemplate());
        $this->assertEquals('%22', $htmlField->obj('RAWURLATT')->forTemplate());
        $this->assertEquals($htmlString, $htmlField->obj('ATT')->forTemplate());
        $this->assertEquals($textString, $htmlField->obj('RAW')->forTemplate());
        $this->assertEquals('\"', $htmlField->obj('JS')->forTemplate());
        $this->assertEquals($htmlString, $htmlField->obj('HTML')->forTemplate());
        $this->assertEquals($htmlString, $htmlField->obj('XML')->forTemplate());

        $textField = DBField::create_field('Text', $textString);
        $this->assertEquals($htmlString, $textField->forTemplate());
        $this->assertEquals($htmlString, $textField->obj('HTMLATT')->forTemplate());
        $this->assertEquals('%22', $textField->obj('URLATT')->forTemplate());
        $this->assertEquals('%22', $textField->obj('RAWURLATT')->forTemplate());
        $this->assertEquals($htmlString, $textField->obj('ATT')->forTemplate());
        $this->assertEquals($textString, $textField->obj('RAW')->forTemplate());
        $this->assertEquals('\"', $textField->obj('JS')->forTemplate());
        $this->assertEquals($htmlString, $textField->obj('HTML')->forTemplate());
        $this->assertEquals($htmlString, $textField->obj('XML')->forTemplate());
    }

    public function testRequiresCasting()
    {
        $caster = new ModelDataTest\Castable();

        $this->assertInstanceOf(ModelDataTest\RequiresCasting::class, $caster->obj('alwaysCasted'));
        $this->assertInstanceOf(ModelDataTest\Caster::class, $caster->obj('noCastingInformation'));

        $this->assertInstanceOf(DBText::class, $caster->obj('arrayOne'));
        $this->assertInstanceOf(ArrayList::class, $caster->obj('arrayTwo'));
    }

    public function testFailoverRequiresCasting()
    {
        $caster = new ModelDataTest\Castable();
        $container = new ModelDataTest\Container();
        $container->setFailover($caster);

        $this->assertInstanceOf(ModelDataTest\RequiresCasting::class, $container->obj('alwaysCasted'));
        $this->assertInstanceOf(ModelDataTest\RequiresCasting::class, $caster->obj('alwaysCasted'));

        $this->assertInstanceOf(ModelDataTest\Caster::class, $container->obj('noCastingInformation'));
        $this->assertInstanceOf(ModelDataTest\Caster::class, $caster->obj('noCastingInformation'));
    }

    public function testCastingXMLVal()
    {
        $caster = new ModelDataTest\Castable();

        $this->assertEquals('casted', $caster->XML_val('alwaysCasted'));
        $this->assertEquals('casted', $caster->XML_val('noCastingInformation'));

        // Test automatic escaping is applied even to fields with no 'casting'
        $this->assertEquals('casted', $caster->XML_val('unsafeXML'));
        $this->assertEquals('&lt;foo&gt;', $caster->XML_val('castedUnsafeXML'));
    }

    public function testArrayCustomise()
    {
        $modelData    = new ModelDataTest\Castable();
        $newModelData = $modelData->customise(
            [
            'test'         => 'overwritten',
            'alwaysCasted' => 'overwritten'
             ]
        );

        $this->assertEquals('test', $modelData->XML_val('test'));
        $this->assertEquals('casted', $modelData->XML_val('alwaysCasted'));

        $this->assertEquals('overwritten', $newModelData->XML_val('test'));
        $this->assertEquals('overwritten', $newModelData->XML_val('alwaysCasted'));

        $this->assertEquals('castable', $modelData->forTemplate());
        $this->assertEquals('castable', $newModelData->forTemplate());
    }

    public function testObjectCustomise()
    {
        $modelData    = new ModelDataTest\Castable();
        $newModelData = $modelData->customise(new ModelDataTest\RequiresCasting());

        $this->assertEquals('test', $modelData->XML_val('test'));
        $this->assertEquals('casted', $modelData->XML_val('alwaysCasted'));

        $this->assertEquals('overwritten', $newModelData->XML_val('test'));
        $this->assertEquals('casted', $newModelData->XML_val('alwaysCasted'));

        $this->assertEquals('castable', $modelData->forTemplate());
        $this->assertEquals('casted', $newModelData->forTemplate());
    }

    public function testDefaultValueWrapping()
    {
        $data = new ArrayData(['Title' => 'SomeTitleValue']);
        // this results in a cached raw string in ModelData:
        $this->assertTrue($data->hasValue('Title'));
        $this->assertFalse($data->hasValue('SomethingElse'));
        // this should cast the raw string to a StringField since we are
        // passing true as the third argument:
        $obj = $data->obj('Title', [], true);
        $this->assertTrue(is_object($obj));
        // and the string field should have the value of the raw string:
        $this->assertEquals('SomeTitleValue', $obj->forTemplate());
    }

    public function testCastingClass()
    {
        $expected = [
            //'NonExistant'   => null,
            'Field'         => 'CastingType',
            'Argument'      => 'ArgumentType',
            'ArrayArgument' => 'ArrayArgumentType'
        ];
        $obj = new ModelDataTest\CastingClass();

        foreach ($expected as $field => $class) {
            $this->assertEquals(
                $class,
                $obj->castingClass($field),
                "castingClass() returns correct results for ::\$$field"
            );
        }
    }

    public function testObjWithCachedStringValueReturnsValidObject()
    {
        $obj = new ModelDataTest\NoCastingInformation();

        // Save a literal string into cache
        $cache = true;
        $uncastedData = $obj->obj('noCastingInformation', [], false, $cache);

        // Fetch the cached string as an object
        $forceReturnedObject = true;
        $castedData = $obj->obj('noCastingInformation', [], $forceReturnedObject);

        // Uncasted data should always be the nonempty string
        $this->assertNotEmpty($uncastedData, 'Uncasted data was empty.');
        //$this->assertTrue(is_string($uncastedData), 'Uncasted data should be a string.');

        // Casted data should be the string wrapped in a DBField-object.
        $this->assertNotEmpty($castedData, 'Casted data was empty.');
        $this->assertInstanceOf(DBField::class, $castedData, 'Casted data should be instance of DBField.');

        $this->assertEquals($uncastedData, $castedData->getValue(), 'Casted and uncasted strings are not equal.');
    }

    public function testCaching()
    {
        $objCached = new ModelDataTest\Cached();
        $objNotCached = new ModelDataTest\NotCached();

        $objCached->Test = 'AAA';
        $objNotCached->Test = 'AAA';

        $this->assertEquals('AAA', $objCached->obj('Test', [], true, true));
        $this->assertEquals('AAA', $objNotCached->obj('Test', [], true, true));

        $objCached->Test = 'BBB';
        $objNotCached->Test = 'BBB';

        // Cached data must be always the same
        $this->assertEquals('AAA', $objCached->obj('Test', [], true, true));
        $this->assertEquals('BBB', $objNotCached->obj('Test', [], true, true));
    }

    public function testSetFailover()
    {
        $failover = new ModelData();
        $container = new ModelDataTest\Container();
        $container->setFailover($failover);

        $this->assertSame($failover, $container->getFailover(), 'getFailover() returned a different object');
        $this->assertFalse($container->hasMethod('testMethod'), 'testMethod() is already defined when it shouldn’t be');

        // Ensure that defined methods detected from the failover aren't cached when setting a new failover
        $container->setFailover(new ModelDataTest\Failover);
        $this->assertTrue($container->hasMethod('testMethod'));

        // Test the reverse - that defined methods previously detected in a failover are removed if they no longer exist
        $container->setFailover($failover);
        $this->assertSame($failover, $container->getFailover(), 'getFailover() returned a different object');
        $this->assertFalse($container->hasMethod('testMethod'), 'testMethod() incorrectly reported as existing');
    }

    public function testIsAccessibleMethod()
    {
        $reflectionMethod = new ReflectionMethod(ModelData::class, 'isAccessibleMethod');
        $reflectionMethod->setAccessible(true);
        $object = new ModelDataTestObject();
        $modelData = new ModelData();

        $output = $reflectionMethod->invokeArgs($object, ['privateMethod']);
        $this->assertFalse($output, 'Method should not be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['protectedMethod']);
        $this->assertTrue($output, 'Method should be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['publicMethod']);
        $this->assertTrue($output, 'Method should be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['missingMethod']);
        $this->assertFalse($output, 'Method should not be accessible');

        $output = $reflectionMethod->invokeArgs($modelData, ['isAccessibleProperty']);
        $this->assertTrue($output, 'Method should be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['publicMethodFromExtension']);
        $this->assertTrue($output, 'Method should be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['protectedMethodFromExtension']);
        $this->assertFalse($output, 'Method should not be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['privateMethodFromExtension']);
        $this->assertFalse($output, 'Method should not be accessible');
    }

    public function testIsAccessibleProperty()
    {
        $reflectionMethod = new ReflectionMethod(ModelData::class, 'isAccessibleProperty');
        $reflectionMethod->setAccessible(true);
        $object = new ModelDataTestObject();

        $output = $reflectionMethod->invokeArgs($object, ['privateProperty']);
        $this->assertFalse($output, 'Property should not be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['protectedProperty']);
        $this->assertTrue($output, 'Property should be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['publicProperty']);
        $this->assertTrue($output, 'Property should be accessible');

        $output = $reflectionMethod->invokeArgs($object, ['missingProperty']);
        $this->assertFalse($output, 'Property should not be accessible');

        $output = $reflectionMethod->invokeArgs(new ModelData(), ['objCache']);
        $this->assertTrue($output, 'Property should be accessible');
    }

    public function testDynamicData()
    {
        $obj = (object) ['SomeField' => [1, 2, 3]];
        $modelData = new ModelData();
        $this->assertFalse($modelData->hasDynamicData('abc'));
        $modelData->setDynamicData('abc', $obj);
        $this->assertTrue($modelData->hasDynamicData('abc'));
        $this->assertSame($obj, $modelData->getDynamicData('abc'));
        $this->assertSame($obj, $modelData->abc);
    }

    public static function provideWrapArrayInObj(): array
    {
        return [
            'empty array' => [
                'arr' => [],
                'expectedClass' => ArrayList::class,
            ],
            'fully indexed array' => [
                'arr' => [
                    'value1',
                    'value2',
                ],
                'expectedClass' => ArrayList::class,
            ],
        ];
    }

    #[DataProvider('provideWrapArrayInObj')]
    public function testWrapArrayInObj(array $arr, string $expectedClass): void
    {
        $modelData = new ModelData();
        $modelData->arr = $arr;
        $this->assertInstanceOf($expectedClass, $modelData->obj('arr'));
    }
}