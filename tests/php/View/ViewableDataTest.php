<?php

namespace SilverStripe\View\Tests;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;

/**
 * See {@link SSViewerTest->testCastingHelpers()} for more tests related to casting and ViewableData behaviour,
 * from a template-parsing perspective.
 */
class ViewableDataTest extends SapphireTest
{

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
        $caster = new ViewableDataTest\Castable();

        $this->assertInstanceOf(ViewableDataTest\RequiresCasting::class, $caster->obj('alwaysCasted'));
        $this->assertInstanceOf(ViewableDataTest\Caster::class, $caster->obj('noCastingInformation'));
    }

    public function testFailoverRequiresCasting()
    {
        $caster = new ViewableDataTest\Castable();
        $container = new ViewableDataTest\Container();
        $container->setFailover($caster);

        $this->assertInstanceOf(ViewableDataTest\RequiresCasting::class, $container->obj('alwaysCasted'));
        $this->assertInstanceOf(ViewableDataTest\RequiresCasting::class, $caster->obj('alwaysCasted'));

        $this->assertInstanceOf(ViewableDataTest\Caster::class, $container->obj('noCastingInformation'));
        $this->assertInstanceOf(ViewableDataTest\Caster::class, $caster->obj('noCastingInformation'));
    }

    public function testCastingXMLVal()
    {
        $caster = new ViewableDataTest\Castable();

        $this->assertEquals('casted', $caster->XML_val('alwaysCasted'));
        $this->assertEquals('casted', $caster->XML_val('noCastingInformation'));

        // Test automatic escaping is applied even to fields with no 'casting'
        $this->assertEquals('casted', $caster->XML_val('unsafeXML'));
        $this->assertEquals('&lt;foo&gt;', $caster->XML_val('castedUnsafeXML'));
    }

    public function testArrayCustomise()
    {
        $viewableData    = new ViewableDataTest\Castable();
        $newViewableData = $viewableData->customise(
            array (
            'test'         => 'overwritten',
            'alwaysCasted' => 'overwritten'
            )
        );

        $this->assertEquals('test', $viewableData->XML_val('test'));
        $this->assertEquals('casted', $viewableData->XML_val('alwaysCasted'));

        $this->assertEquals('overwritten', $newViewableData->XML_val('test'));
        $this->assertEquals('overwritten', $newViewableData->XML_val('alwaysCasted'));

        $this->assertEquals('castable', $viewableData->forTemplate());
        $this->assertEquals('castable', $newViewableData->forTemplate());
    }

    public function testObjectCustomise()
    {
        $viewableData    = new ViewableDataTest\Castable();
        $newViewableData = $viewableData->customise(new ViewableDataTest\RequiresCasting());

        $this->assertEquals('test', $viewableData->XML_val('test'));
        $this->assertEquals('casted', $viewableData->XML_val('alwaysCasted'));

        $this->assertEquals('overwritten', $newViewableData->XML_val('test'));
        $this->assertEquals('casted', $newViewableData->XML_val('alwaysCasted'));

        $this->assertEquals('castable', $viewableData->forTemplate());
        $this->assertEquals('casted', $newViewableData->forTemplate());
    }

    public function testDefaultValueWrapping()
    {
        $data = new ArrayData(array('Title' => 'SomeTitleValue'));
        // this results in a cached raw string in ViewableData:
        $this->assertTrue($data->hasValue('Title'));
        $this->assertFalse($data->hasValue('SomethingElse'));
        // this should cast the raw string to a StringField since we are
        // passing true as the third argument:
        $obj = $data->obj('Title', null, true);
        $this->assertTrue(is_object($obj));
        // and the string field should have the value of the raw string:
        $this->assertEquals('SomeTitleValue', $obj->forTemplate());
    }

    public function testCastingClass()
    {
        $expected = array(
            //'NonExistant'   => null,
            'Field'         => 'CastingType',
            'Argument'      => 'ArgumentType',
            'ArrayArgument' => 'ArrayArgumentType'
        );
        $obj = new ViewableDataTest\CastingClass();

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
        $obj = new ViewableDataTest\NoCastingInformation();

        // Save a literal string into cache
        $cache = true;
        $uncastedData = $obj->obj('noCastingInformation', null, false, $cache);

        // Fetch the cached string as an object
        $forceReturnedObject = true;
        $castedData = $obj->obj('noCastingInformation', null, $forceReturnedObject);

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
        $objCached = new ViewableDataTest\Cached();
        $objNotCached = new ViewableDataTest\NotCached();

        $objCached->Test = 'AAA';
        $objNotCached->Test = 'AAA';

        $this->assertEquals('AAA', $objCached->obj('Test', null, true, true));
        $this->assertEquals('AAA', $objNotCached->obj('Test', null, true, true));

        $objCached->Test = 'BBB';
        $objNotCached->Test = 'BBB';

        // Cached data must be always the same
        $this->assertEquals('AAA', $objCached->obj('Test', null, true, true));
        $this->assertEquals('BBB', $objNotCached->obj('Test', null, true, true));
    }

    public function testSetFailover()
    {
        $failover = new ViewableData();
        $container = new ViewableDataTest\Container();
        $container->setFailover($failover);

        $this->assertSame($failover, $container->getFailover(), 'getFailover() returned a different object');
        $this->assertFalse($container->hasMethod('testMethod'), 'testMethod() is already defined when it shouldn’t be');

        // Ensure that defined methods detected from the failover aren't cached when setting a new failover
        $container->setFailover(new ViewableDataTest\Failover);
        $this->assertTrue($container->hasMethod('testMethod'));

        // Test the reverse - that defined methods previously detected in a failover are removed if they no longer exist
        $container->setFailover($failover);
        $this->assertSame($failover, $container->getFailover(), 'getFailover() returned a different object');
        $this->assertFalse($container->hasMethod('testMethod'), 'testMethod() incorrectly reported as existing');
    }

    public function testThemeDir()
    {
        $themes = [
            "silverstripe/framework:/tests/php/View/ViewableDataTest/testtheme",
            SSViewer::DEFAULT_THEME
        ];
        SSViewer::set_themes($themes);

        $data = new ViewableData();
        $this->assertContains(
            'tests/php/View/ViewableDataTest/testtheme',
            $data->ThemeDir()
        );
    }
}
