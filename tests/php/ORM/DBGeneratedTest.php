<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\ORM\FieldType;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBGenerated;
use SilverStripe\ORM\Tests\DBGeneratedTest\TestDBField;

class DBGeneratedTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideGetChildField(): array
    {
        return [
            'BigInt' => [
                'fieldSpec' => 'BigInt',
                'expectedChildClass' => FieldType\DBBigInt::class,
            ],
            'Boolean' => [
                'fieldSpec' => 'Boolean',
                'expectedChildClass' => FieldType\DBBoolean::class,
            ],
            'Currency' => [
                'fieldSpec' => 'Currency',
                'expectedChildClass' => FieldType\DBCurrency::class,
            ],
            'Date' => [
                'fieldSpec' => 'Date',
                'expectedChildClass' => FieldType\DBDate::class,
            ],
            'Datetime' => [
                'fieldSpec' => 'Datetime',
                'expectedChildClass' => FieldType\DBDatetime::class,
            ],
            'Decimal' => [
                'fieldSpec' => 'Decimal',
                'expectedChildClass' => FieldType\DBDecimal::class,
            ],
            'Double' => [
                'fieldSpec' => 'Double',
                'expectedChildClass' => FieldType\DBDouble::class,
            ],
            'Email' => [
                'fieldSpec' => 'Email',
                'expectedChildClass' => FieldType\DBEmail::class,
            ],
            'Enum' => [
                'fieldSpec' => 'Enum',
                'expectedChildClass' => FieldType\DBEnum::class,
            ],
            'Float' => [
                'fieldSpec' => 'Float',
                'expectedChildClass' => FieldType\DBFloat::class,
            ],
            'HTMLFragment' => [
                'fieldSpec' => 'HTMLFragment',
                'expectedChildClass' => FieldType\DBHTMLText::class,
            ],
            'HTMLText' => [
                'fieldSpec' => 'HTMLText',
                'expectedChildClass' => FieldType\DBHTMLText::class,
            ],
            'HTMLVarchar' => [
                'fieldSpec' => 'HTMLVarchar',
                'expectedChildClass' => FieldType\DBHTMLVarchar::class,
            ],
            'Int' => [
                'fieldSpec' => 'Int',
                'expectedChildClass' => FieldType\DBInt::class,
            ],
            'IP' => [
                'fieldSpec' => 'IP',
                'expectedChildClass' => FieldType\DBIp::class,
            ],
            'Locale' => [
                'fieldSpec' => 'Locale',
                'expectedChildClass' => FieldType\DBLocale::class,
            ],
            'MultiEnum' => [
                'fieldSpec' => 'MultiEnum',
                'expectedChildClass' => FieldType\DBMultiEnum::class,
            ],
            'Percentage' => [
                'fieldSpec' => 'Percentage',
                'expectedChildClass' => FieldType\DBPercentage::class,
            ],
            'Text' => [
                'fieldSpec' => 'Text',
                'expectedChildClass' => FieldType\DBText::class,
            ],
            'Time' => [
                'fieldSpec' => 'Time',
                'expectedChildClass' => FieldType\DBTime::class,
            ],
            'URL' => [
                'fieldSpec' => 'URL',
                'expectedChildClass' => FieldType\DBUrl::class,
            ],
            'Varchar' => [
                'fieldSpec' => 'Varchar',
                'expectedChildClass' => FieldType\DBVarchar::class,
            ],
            'Year' => [
                'fieldSpec' => 'Year',
                'expectedChildClass' => FieldType\DBYear::class,
            ],
        ];
    }

    #[DataProvider('provideGetChildField')]
    public function testGetChildField(string $fieldSpec, string $expectedChildClass): void
    {
        $field = new DBGenerated('MyField', $fieldSpec, 'any expression');
        // Don't use assertInstanceOf because we want to check the exact class, not including subclasses
        $this->assertSame($expectedChildClass, get_class($field->getChildField()));
    }

    public static function provideInvalidFieldSpec(): array
    {
        return [
            [
                'fieldSpec' => 'Money',
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . FieldType\DBMoney::class . '\'.',
            ],
            [
                'fieldSpec' => 'ForeignKey',
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . FieldType\DBForeignKey::class . '\'.',
            ],
            [
                'fieldSpec' => 'PolymorphicForeignKey',
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . FieldType\DBPolymorphicForeignKey::class . '\'.',
            ],
            [
                'fieldSpec' => 'PolymorphicRelationAwareForeignKey',
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . FieldType\DBPolymorphicRelationAwareForeignKey::class . '\'.',
            ],
            [
                'fieldSpec' => 'PrimaryKey',
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . FieldType\DBPrimaryKey::class . '\'.',
            ],
            [
                'fieldSpec' => 'DBClassName',
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . FieldType\DBClassName::class . '\'.',
            ],
            [
                'fieldSpec' => 'DBClassNameVarchar',
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . FieldType\DBClassNameVarchar::class . '\'.',
            ],
            [
                'fieldSpec' => TestDBField::class,
                'expectedExceptionClass' => InvalidArgumentException::class,
                'expectedExceptionMessage' => 'Cannot create a generated field based on class \'' . TestDBField::class . '\' - it needs a \'getFieldSpec\' method.',
            ],
            [
                'fieldSpec' => 'SomeRandomNonsense',
                'expectedExceptionClass' => InjectorNotFoundException::class,
                'expectedExceptionMessage' => 'Class SomeRandomNonsense does not exist',
            ],
        ];
    }

    #[DataProvider('provideInvalidFieldSpec')]
    public function testInvalidFieldSpec(string $fieldSpec, string $expectedExceptionClass, string $expectedExceptionMessage): void
    {
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedExceptionMessage);
        new DBGenerated('MyField', $fieldSpec, 'any expression');
    }

    public static function provideAdvancedFieldSpec(): array
    {
        // Note that this is not exhaustive - we don't need to test that all constructors
        // for all DBField types use the arguments in the expected ways.
        // This just tests that in general the fieldspec is correctly passed through and
        // sets options in the underlying data type.
        return [
            [
                'fieldSpec' => 'Varchar(123, ["nullifyEmpty" => false])',
                'methodCallsAndResults' => [
                    'getSize' => 123,
                    'getNullifyEmpty' => false,
                ],
            ],
            [
                'fieldSpec' => 'Decimal(25, 30)',
                'methodCallsAndResults' => [
                    'getWholeSize' => 25,
                    'getDecimalSize' => 30,
                ],
            ],
            [
                'fieldSpec' => 'Enum(["val1", "val2", "val3"])',
                'methodCallsAndResults' => [
                    'getEnum' => ['val1', 'val2', 'val3'],
                ],
            ],
            [
                'fieldSpec' => 'HTMLText(["whitelist" => "one,two,three"])',
                'methodCallsAndResults' => [
                    'getWhitelist' => ['one', 'two', 'three'],
                ],
            ],
        ];
    }

    #[DataProvider('provideAdvancedFieldSpec')]
    public function testAdvancedFieldSpec(string $fieldSpec, array $methodCallsAndResults): void
    {
        $field = new DBGenerated('MyField', $fieldSpec, 'any expression');
        $childField = $field->getChildField();
        foreach ($methodCallsAndResults as $method => $expected) {
            $this->assertSame($expected, $childField->$method());
        }
    }

    public static function provideMethodCalls(): array
    {
        $scenarios = static::provideGetChildField();
        foreach ($scenarios as &$scenario) {
            unset($scenario['expectedChildClass']);
        }
        return $scenarios;
    }

    /**
     * Tests that public methods (if explicitly defined on DBField) get passed through
     * to the underlying data representation where appropriate.
     * Note that we use fieldspecs for all valid DBField instances to ensure there aren't
     * any unexpected cases.
     */
    #[DataProvider('provideMethodCalls')]
    public function testMethodCalls(string $fieldSpec): void
    {
        // Prep field
        $field = new DBGenerated('MyField', $fieldSpec, 'any expression');
        $field->setValue(123);
        $field->setDefaultValue(456);
        $field->setTable('mytable');
        $field->setArrayValue(['a', 'b', 'c']);
        $field->setIndexType(FieldType\DBIndexable::TYPE_INDEX);
        $childField = $field->getChildField();

        // Find methods
        $methods = get_class_methods(DBField::class);
        // Skip some methods explicitly
        $methods = array_diff($methods, [
            'addToQuery',
            'getValueForValidation',
            'getOptions',
            'prepValueForDB',
            'requireField',
            'saveInto',
            'uninherited',
            'validate',
            'writeToManipulation',
        ]);

        $reflectionGeneratedColumn = new ReflectionClass(DBField::class);
        foreach ($methods as $method) {
            // Magic methods don't count
            if (str_starts_with($method, '__')) {
                continue;
            }
            // Skip setters
            if (str_starts_with($method, 'set')) {
                continue;
            }
            // Skip static or non-public methods which don't pass through
            $reflectionMethod = $reflectionGeneratedColumn->getMethod($method);
            if ($reflectionMethod->isStatic() || !$reflectionMethod->isPublic()) {
                continue;
            }
            // Skip any inherited methods
            if (!in_array($reflectionMethod->getDeclaringClass()->getName(), [DBField::class])) {
                continue;
            }

            switch ($method) {
                case 'defaultSearchFilter':
                    $childFilter = $childField->$method();
                    $mainFilter = $field->$method();
                    $this->assertSame(get_class($childFilter), get_class($mainFilter));
                    break;
                case 'scaffoldSearchField':
                    $childFormField = $childField->$method();
                    $mainFormField = $field->$method();
                    $this->assertSame(get_class($childFormField), get_class($mainFormField));
                    $this->assertSame($childFormField->title(), $mainFormField->title());
                    break;
                case 'scaffoldFormField':
                    $childFormField = $childField->$method();
                    $mainFormField = $field->$method();
                    // note the readonly transformation
                    $this->assertSame(get_class($childFormField->performReadonlyTransformation()), get_class($mainFormField));
                    $this->assertSame($childFormField->title(), $mainFormField->title());
                    break;
                default:
                    $this->assertSame($childField->$method(), $field->$method(), "testing $method");
            }
        }
    }

    public function testFailover()
    {
        // this is intentionally not exhaustive - we're just making sure that in general methods failover to the child class.
        $field = new DBGenerated('MyField', 'HTMLText', 'any expression');
        $this->assertSame($field->getChildField(), $field->getFailover());
        $this->assertSame($field->getChildField()->LowerCase(), $field->LowerCase());
    }

    public function testForTemplate()
    {
        $field = new DBGenerated('MyField', 'HTMLText', 'any expression');
        $field->setValue('<div><p><a href="[sitetree_link id=123]">text here</a></p></div>');
        $this->assertSame($field->getChildField()->forTemplate(), $field->forTemplate());
    }

    public function testCastingHelper()
    {
        $field = new DBGenerated('MyField', 'HTMLText', 'any expression');
        $this->assertSame($field->getChildField()->castingHelper('LowerCase'), $field->castingHelper('LowerCase'));
    }

    public function testObj()
    {
        $field = new DBGenerated('MyField', 'HTMLText', 'any expression');
        $this->assertSame($field->getChildField()->obj('LowerCase')?->getValue(), $field->obj('LowerCase')?->getValue());
    }

    public function testHasValue()
    {
        $field = new DBGenerated('MyField', 'HTMLText', 'any expression');
        $this->assertSame($field->getChildField()->hasValue('LowerCase'), $field->hasValue('LowerCase'));
    }
}
