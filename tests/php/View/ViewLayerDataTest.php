<?php

namespace SilverStripe\View\Tests;

use ArrayIterator;
use BadMethodCallException;
use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Exception\MissingTemplateException;
use SilverStripe\View\Tests\ViewLayerDataTest\CountableObject;
use SilverStripe\View\Tests\ViewLayerDataTest\ExtensibleObject;
use SilverStripe\View\Tests\ViewLayerDataTest\ExtensibleObjectExtension;
use SilverStripe\View\Tests\ViewLayerDataTest\GetCountObject;
use SilverStripe\View\Tests\ViewLayerDataTest\NonIterableObject;
use SilverStripe\View\Tests\ViewLayerDataTest\StringableObject;
use SilverStripe\View\Tests\ViewLayerDataTest\TestBadMethodCallObjectSubclass;
use SilverStripe\View\Tests\ViewLayerDataTest\TestFixture;
use SilverStripe\View\Tests\ViewLayerDataTest\TestFixtureComplex;
use SilverStripe\View\ViewLayerData;
use stdClass;
use Throwable;

class ViewLayerDataTest extends SapphireTest
{
    protected static $required_extensions = [
        ExtensibleObject::class => [
            ExtensibleObjectExtension::class
        ],
    ];

    public static function provideGetIterator(): array
    {
        return [
            'non-iterable object' => [
                'data' => new ArrayData(['Field1' => 'value1', 'Field2' => 'value2']),
                'expected' => BadMethodCallException::class,
            ],
            'non-iterable scalar' => [
                'data' => 'This is some text, aint iterable',
                'expected' => BadMethodCallException::class,
            ],
            'empty array' => [
                'data' => [],
                'expected' => [],
            ],
            'single item array' => [
                'data' => ['one value'],
                'expected' => ['one value'],
            ],
            'multi-item array' => [
                'data' => ['one', 'two', 'three'],
                'expected' => ['one', 'two', 'three'],
            ],
            'object implements an Iterable interface' => [
                'data' => new ArrayList(['one', 'two', 'three']),
                'expected' => ['one', 'two', 'three'],
            ],
            'built-in PHP iterator' => [
                'data' => new ArrayIterator(['one', 'two', 'three']),
                'expected' => ['one', 'two', 'three'],
            ],
            'non-iterable object with getIterator method' => [
                'data' => new NonIterableObject(),
                'expected' => ['some value', 'another value', 'isnt this nice'],
            ],
            'extensible object with getIterator extension' => [
                'data' => new ExtensibleObject(),
                'expected' => ['1','2','3','4','5','6','7','8','9','a','b','c','d','e'],
            ],
        ];
    }

    #[DataProvider('provideGetIterator')]
    public function testGetIterator(mixed $data, string|array $expected): void
    {
        $viewLayerData = new ViewLayerData($data);
        if ($expected === BadMethodCallException::class) {
            $this->expectException(BadMethodCallException::class);
            $this->expectExceptionMessageMatches('/is not iterable.$/');
        }
        $this->assertEquals($expected, iterator_to_array($viewLayerData->getIterator()));
        // Ensure the iterator is always wrapping values
        foreach ($viewLayerData as $value) {
            $this->assertInstanceOf(ViewLayerData::class, $value);
        }
    }

    public static function provideGetIteratorCount(): array
    {
        return [
            'uncountable object' => [
                'data' => new ArrayData(['Field1' => 'value1', 'Field2' => 'value2']),
                'expected' => 0,
            ],
            'uncountable object - has count field' => [
                'data' => new ArrayData(['count' => 12, 'Field2' => 'value2']),
                'expected' => 12,
            ],
            'uncountable object - has count field (non-int)' => [
                'data' => new ArrayData(['count' => 'aahhh', 'Field2' => 'value2']),
                'expected' => 0,
            ],
            'empty array' => [
                'data' => [],
                'expected' => 0,
            ],
            'array with values' => [
                'data' => [1, 2],
                'expected' => 2,
            ],
            'explicitly countable object' => [
                'data' => new CountableObject(),
                'expected' => 53,
            ],
            'non-countable object with getCount method' => [
                'data' => new GetCountObject(),
                'expected' => 12,
            ],
            'non-countable object with getIterator method' => [
                'data' => new NonIterableObject(),
                'expected' => 3,
            ],
            'extensible object with getIterator extension' => [
                'data' => new ExtensibleObject(),
                'expected' => 14,
            ],
        ];
    }

    #[DataProvider('provideGetIteratorCount')]
    public function testGetIteratorCount(mixed $data, int $expected): void
    {
        $viewLayerData = new ViewLayerData($data);
        $this->assertSame($expected, $viewLayerData->getIteratorCount());
    }

    public static function provideIsSet(): array
    {
        return [
            'list array' => [
                'data' => ['anything'],
                'name' => 'anything',
                'expected' => false,
            ],
            'associative array has key' => [
                'data' => ['anything' => 'some value'],
                'name' => 'anything',
                'expected' => true,
            ],
            'ModelData without field' => [
                'data' => new ArrayData(['nothing' => 'some value']),
                'name' => 'anything',
                'expected' => false,
            ],
            'ModelData with field' => [
                'data' => new ArrayData(['anything' => 'some value']),
                'name' => 'anything',
                'expected' => true,
            ],
            'extensible class with getter extension' => [
                'data' => new ExtensibleObject(),
                'name' => 'anything',
                'expected' => true,
            ],
            'extensible class not set' => [
                'data' => new ExtensibleObject(),
                'name' => 'anythingelse',
                'expected' => false,
            ],
            'class with method' => [
                'data' => new CountableObject(),
                'name' => 'count',
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('provideIsSet')]
    public function testIsSet(mixed $data, string $name, bool $expected): void
    {
        $viewLayerData = new ViewLayerData($data);
        $this->assertSame($expected, isset($viewLayerData->$name));
    }

    public static function provideGet(): array
    {
        return [
            'basic field' => [
                'name' => 'SomeField',
                'throwException' => true,
                'expected' => [
                    [
                        'type' => 'method',
                        'name' => 'SomeField',
                        'args' => [],
                    ],
                    [
                        'type' => 'method',
                        'name' => 'getSomeField',
                        'args' => [],
                    ],
                    [
                        'type' => 'property',
                        'name' => 'SomeField',
                    ],
                ],
            ],
            'getter as property' => [
                'name' => 'getSomeField',
                'throwException' => true,
                'expected' => [
                    [
                        'type' => 'method',
                        'name' => 'getSomeField',
                        'args' => [],
                    ],
                    [
                        'type' => 'method',
                        'name' => 'getgetSomeField',
                        'args' => [],
                    ],
                    [
                        'type' => 'property',
                        'name' => 'getSomeField',
                    ],
                ],
            ],
            'basic field (lowercase)' => [
                'name' => 'somefield',
                'throwException' => true,
                'expected' => [
                    [
                        'type' => 'method',
                        'name' => 'somefield',
                        'args' => [],
                    ],
                    [
                        'type' => 'method',
                        'name' => 'getsomefield',
                        'args' => [],
                    ],
                    [
                        'type' => 'property',
                        'name' => 'somefield',
                    ],
                ],
            ],
            'property not set, dont even try it' => [
                'name' => 'NotSet',
                'throwException' => true,
                'expected' => [
                    [
                        'type' => 'method',
                        'name' => 'NotSet',
                        'args' => [],
                    ],
                    [
                        'type' => 'method',
                        'name' => 'getNotSet',
                        'args' => [],
                    ],
                ],
            ],
            'stops after method when not throwing' => [
                'name' => 'SomeField',
                'throwException' => false,
                'expected' => [
                    [
                        'type' => 'method',
                        'name' => 'SomeField',
                        'args' => [],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideGet')]
    public function testGet(string $name, bool $throwException, array $expected): void
    {
        $fixture = new TestFixture();
        $fixture->throwException = $throwException;
        $viewLayerData = new ViewLayerData($fixture);
        $value = $viewLayerData->$name;
        $this->assertSame($expected, $fixture->getRequested());
        $this->assertNull($value);
    }

    public static function provideGetComplex(): array
    {
        // Note the actual value checks aren't very comprehensive here because that's done
        // in more detail in testGetRawDataValue
        return [
            'exception gets thrown if not __call() method' => [
                'name' => 'badMethodCall',
                'expectRequested' => BadMethodCallException::class,
                'expected' => null,
            ],
            'returning nothing is like returning null' => [
                'name' => 'voidMethod',
                'expectRequested' => [
                    [
                        'type' => 'method',
                        'name' => 'voidMethod',
                        'args' => [],
                    ],
                ],
                'expected' => null,
            ],
            'returned value is caught' => [
                'name' => 'justCallMethod',
                'expectRequested' => [
                    [
                        'type' => 'method',
                        'name' => 'justCallMethod',
                        'args' => [],
                    ],
                ],
                'expected' => 'This is a method value',
            ],
            'getter is used' => [
                'name' => 'ActualValue',
                'expectRequested' => [
                    [
                        'type' => 'method',
                        'name' => 'getActualValue',
                        'args' => [],
                    ],
                ],
                'expected' => 'this is the value',
            ],
            'if no method exists, only property is fetched' => [
                'name' => 'NoMethod',
                'expectRequested' => [
                    [
                        'type' => 'property',
                        'name' => 'NoMethod',
                    ],
                ],
                'expected' => null,
            ],
            'property value is caught' => [
                'name' => 'ActualValueField',
                'expectRequested' => [
                    [
                        'type' => 'property',
                        'name' => 'ActualValueField',
                    ],
                ],
                'expected' => 'the value is here',
            ],
            'not set and no method' => [
                'name' => 'NotSet',
                'expectRequested' => [],
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('provideGetComplex')]
    public function testGetComplex(string $name, string|array $expectRequested, ?string $expected): void
    {
        $fixture = new TestFixtureComplex();
        $viewLayerData = new ViewLayerData($fixture);
        if ($expectRequested === BadMethodCallException::class) {
            $this->expectException(BadMethodCallException::class);
        }
        $value = $viewLayerData->$name;
        $this->assertSame($expectRequested, $fixture->getRequested());
        $this->assertEquals($expected, $value);
        // Ensure value is being wrapped when not null
        if ($value !== null) {
            $this->assertInstanceOf(ViewLayerData::class, $value);
        }
    }

    public static function provideCall(): array
    {
        // Currently there is no distinction between trying to get a property or call a method from ViewLayerData
        // so the "get" examples should produce the same results when calling a method.
        $scenarios = static::provideGet();
        foreach ($scenarios as &$scenario) {
            $scenario['args'] = [];
        }
        return [
            ...$scenarios,
            'basic field with args' => [
                'name' => 'SomeField',
                'args' => ['abc', 123],
                'throwException' => true,
                'expected' => [
                    [
                        'type' => 'method',
                        'name' => 'SomeField',
                        'args' => ['abc', 123],
                    ],
                    [
                        'type' => 'method',
                        'name' => 'getSomeField',
                        'args' => ['abc', 123],
                    ],
                    [
                        'type' => 'property',
                        'name' => 'SomeField',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideCall')]
    public function testCall(string $name, array $args, bool $throwException, array $expected): void
    {
        $fixture = new TestFixture();
        $fixture->throwException = $throwException;
        $viewLayerData = new ViewLayerData($fixture);
        $value = $viewLayerData->$name(...$args);
        $this->assertSame($expected, $fixture->getRequested());
        $this->assertNull($value);
    }

    public static function provideCallComplex(): array
    {
        // Currently there is no distinction between trying to get a property or call a method from ViewLayerData
        // so the "get" examples should produce the same results when calling a method.
        return static::provideGetComplex();
    }

    #[DataProvider('provideCallComplex')]
    public function testCallComplex(string $name, string|array $expectRequested, ?string $expected): void
    {
        $fixture = new TestFixtureComplex();
        $viewLayerData = new ViewLayerData($fixture);
        if ($expectRequested === BadMethodCallException::class) {
            $this->expectException(BadMethodCallException::class);
        }
        $value = $viewLayerData->$name();
        $this->assertSame($expectRequested, $fixture->getRequested());
        $this->assertEquals($expected, $value);
        // Ensure value is being wrapped when not null
        if ($value !== null) {
            $this->assertInstanceOf(ViewLayerData::class, $value);
        }
    }

    public static function provideToString(): array
    {
        return [
            // These three all evaluate to ArrayList or ArrayData, which don't have templates to render
            'empty array' => [
                'data' => [],
                'expected' => MissingTemplateException::class,
            ],
            'array with values' => [
                'data' => ['value1', 'value2'],
                'expected' => MissingTemplateException::class,
            ],
            'Class with no template' => [
                // Note we won't check classes WITH templates because we're not testing the template engine here
                'data' => new ArrayData(['Field1' => 'value1', 'Field2' => 'value2']),
                'expected' => MissingTemplateException::class,
            ],
            'string value' => [
                'data' => 'just a string',
                'expected' => 'just a string',
            ],
            'html gets escaped by default' => [
                'data' => '<span>HTML string</span>',
                'expected' => '&lt;span&gt;HTML string&lt;/span&gt;',
            ],
            'explicit HTML text not escaped' => [
                'data' => (new DBHTMLText())->setValue('<span>HTML string</span>'),
                'expected' => '<span>HTML string</span>',
            ],
            'DBField' => [
                'data' => (new DBDate())->setValue('2024-03-24'),
                'expected' => (new DBDate())->setValue('2024-03-24')->forTemplate(),
            ],
            '__toString() method' => [
                'data' => new StringableObject(),
                'expected' => 'This is the string representation',
            ],
            'forTemplate called from extension' => [
                'data' => new ExtensibleObject(),
                'expected' => 'This text comes from the extension class',
            ],
            'cannot convert this class to string' => [
                'data' => new CountableObject(),
                'expected' => Error::class,
            ],
        ];
    }

    #[DataProvider('provideToString')]
    public function testToString(mixed $data, string $expected): void
    {
        $viewLayerData = new ViewLayerData($data);
        if (is_a($expected, Throwable::class, true)) {
            $this->expectException($expected);
        }
        $this->assertSame($expected, (string) $viewLayerData);
    }

    public static function provideHasDataValue(): array
    {
        return [
            'empty array' => [
                'data' => [],
                'name' => null,
                'expected' => false,
            ],
            'empty ArrayList' => [
                'data' => new ArrayList(),
                'name' => null,
                'expected' => false,
            ],
            'empty ArrayData' => [
                'data' => new ArrayData(),
                'name' => null,
                'expected' => false,
            ],
            'empty ArrayIterator' => [
                'data' => new ArrayIterator(),
                'name' => null,
                'expected' => false,
            ],
            'empty ModelData' => [
                'data' => new ModelData(),
                'name' => null,
                'expected' => true,
            ],
            'non-countable object' => [
                'data' => new ExtensibleObject(),
                'name' => null,
                'expected' => true,
            ],
            'array with data' => [
                'data' => [1,2,3],
                'name' => null,
                'expected' => true,
            ],
            'associative array' => [
                'data' => ['one' => 1, 'two' => 2],
                'name' => null,
                'expected' => true,
            ],
            'ArrayList with data' => [
                'data' => new ArrayList([1,2,3]),
                'name' => null,
                'expected' => true,
            ],
            'ArrayData with data' => [
                'data' => new ArrayData(['one' => 1, 'two' => 2]),
                'name' => null,
                'expected' => true,
            ],
            'ArrayIterator with data' => [
                'data' => new ArrayIterator([1,2,3]),
                'name' => null,
                'expected' => true,
            ],
            'ArrayData missing value' => [
                'data' => new ArrayData(['one' => 1, 'two' => 2]),
                'name' => 'three',
                'expected' => false,
            ],
            'ArrayData with truthy value' => [
                'data' => new ArrayData(['one' => 1, 'two' => 2]),
                'name' => 'one',
                'expected' => true,
            ],
            'ArrayData with null value' => [
                'data' => new ArrayData(['nullVal' => null, 'two' => 2]),
                'name' => 'nullVal',
                'expected' => false,
            ],
            'ArrayData with falsy value' => [
                'data' => new ArrayData(['zero' => 0, 'two' => 2]),
                'name' => 'zero',
                'expected' => false,
            ],
            'Empty string' => [
                'data' => '',
                'name' => null,
                'expected' => false,
            ],
            'Truthy string' => [
                'data' => 'has a value',
                'name' => null,
                'expected' => true,
            ],
            'Field on a string' => [
                'data' => 'has a value',
                'name' => 'SomeField',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideHasDataValue')]
    public function testHasDataValue(mixed $data, ?string $name, bool $expected): void
    {
        $viewLayerData = new ViewLayerData($data);
        $this->assertSame($expected, $viewLayerData->hasDataValue($name));
    }

    public static function provideGetRawDataValue(): array
    {
        $dbHtml = (new DBHTMLText())->setValue('Some html text');
        // Note we're not checking the fetch order or passing args here - see testGet and testCall for that.
        return [
            [
                'data' => ['MyField' => 'some value'],
                'name' => 'MissingField',
                'expected' => null,
            ],
            [
                'data' => ['MyField' => null],
                'name' => 'MyField',
                'expected' => null,
            ],
            [
                'data' => ['MyField' => 'some value'],
                'name' => 'MyField',
                'expected' => 'some value',
            ],
            [
                'data' => ['MyField' => 123],
                'name' => 'MyField',
                'expected' => 123,
            ],
            [
                'data' => ['MyField' => true],
                'name' => 'MyField',
                'expected' => true,
            ],
            [
                'data' => ['MyField' => false],
                'name' => 'MyField',
                'expected' => false,
            ],
            [
                'data' => ['MyField' => $dbHtml],
                'name' => 'MyField',
                'expected' => $dbHtml,
            ],
            [
                'data' => (new ArrayData(['MyField' => 1234]))->customise(new ArrayData(['MyField' => 'overridden value'])),
                'name' => 'MyField',
                'expected' => 'overridden value',
            ],
            [
                'data' => (new ArrayData(['MyField' => 1234]))->customise(new ArrayData(['FieldTwo' => 'checks here'])),
                'name' => 'FieldTwo',
                'expected' => 'checks here',
            ],
            [
                'data' => (new ArrayData(['MyField' => 1234]))->customise(new ArrayData(['FieldTwo' => 'not here'])),
                'name' => 'MyField',
                'expected' => 1234,
            ],
        ];
    }

    #[DataProvider('provideGetRawDataValue')]
    public function testGetRawDataValue(mixed $data, string $name, mixed $expected): void
    {
        $viewLayerData = new ViewLayerData($data);
        $this->assertSame($expected, $viewLayerData->getRawDataValue($name));
    }

    public function testCache(): void
    {
        $data = new ArrayData(['MyField' => 'some value']);
        $viewLayerData = new ViewLayerData($data);

        // No cache because we haven't fetched anything
        $this->assertNull($data->objCacheGet('MyField'));

        // Fetching the value caches it
        $viewLayerData->MyField;
        $this->assertSame('some value', $data->objCacheGet('MyField'));
    }

    public function testSpecialNames(): void
    {
        $data = new stdClass;
        $viewLayerData = new ViewLayerData($data);

        // Metadata values are available when there's nothing in the actual data
        $this->assertTrue(isset($viewLayerData->ClassName));
        $this->assertTrue(isset($viewLayerData->Me));
        $this->assertSame(stdClass::class, $viewLayerData->getRawDataValue('ClassName')->getValue());
        $this->assertSame($data, $viewLayerData->getRawDataValue('Me'));

        // Metadata values are lower priority than real values in the actual data
        $data->ClassName = 'some other class';
        $data->Me = 'something else';
        $this->assertTrue(isset($viewLayerData->ClassName));
        $this->assertTrue(isset($viewLayerData->Me));
        $this->assertSame('some other class', $viewLayerData->getRawDataValue('ClassName'));
        $this->assertSame('something else', $viewLayerData->getRawDataValue('Me'));
    }

    public static function provideCallDataMethodExceptionCatching(): array
    {
        return [
            'exception thrown directly in __call()' => [
                'method' => 'directMethod',
                'exceptionCaught' => true,
            ],
            'exception thrown in __call() on superclass' => [
                'method' => 'inheritedMethod',
                'exceptionCaught' => true,
            ],
            'exception thrown outside __call()' => [
                'method' => 'realMethod',
                'exceptionCaught' => false,
            ],
            // This happens if one class is used to wrap another, so it relies on calling
            // methods on the wrapped class which may not share its class hierarchy.
            'exception thrown in __call() in a different class hierarchy' => [
                'method' => 'anotherClass',
                'exceptionCaught' => true,
            ],
        ];
    }

    #[DataProvider('provideCallDataMethodExceptionCatching')]
    public function testCallDataMethodExceptionCatching(string $method, bool $exceptionCaught): void
    {
        $data = new TestBadMethodCallObjectSubclass();
        $viewLayerData = new ViewLayerData($data);
        if (!$exceptionCaught) {
            $this->expectException(BadMethodCallException::class);
        }

        $result = $viewLayerData->$method();
        $this->assertNull($result);
    }
}
