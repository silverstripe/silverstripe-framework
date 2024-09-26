<?php

namespace SilverStripe\View\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBTime;
use SilverStripe\View\CastingService;
use SilverStripe\View\Tests\CastingServiceTest\TestDataObject;
use stdClass;

class CastingServiceTest extends SapphireTest
{
    // protected static $extra_dataobjects = [
    //     TestDataObject::class,
    // ];

    protected $usesDatabase = false;

    public static function provideCast(): array
    {
        return [
            [
                'data' => null,
                'source' => null,
                'fieldName' => '',
                'expected' => null,
            ],
            [
                'data' => new stdClass(),
                'source' => null,
                'fieldName' => '',
                'expected' => stdClass::class,
            ],
            [
                'data' => new stdClass(),
                'source' => TestDataObject::class,
                'fieldName' => 'DateField',
                'expected' => stdClass::class,
            ],
            [
                'data' => new DBText(),
                'source' => TestDataObject::class,
                'fieldName' => 'DateField',
                'expected' => stdClass::class,
            ],
            [
                'data' => '2024-10-10',
                'source' => TestDataObject::class,
                'fieldName' => 'DateField',
                'expected' => DBDate::class,
            ],
            [
                'data' => 'some value',
                'source' => TestDataObject::class,
                'fieldName' => 'HtmlField',
                'expected' => DBHTMLText::class,
            ],
            [
                'data' => '12.35',
                'source' => TestDataObject::class,
                'fieldName' => 'OverrideCastingHelper',
                'expected' => DBCurrency::class,
            ],
            [
                'data' => '10:17:36',
                'source' => TestDataObject::class,
                'fieldName' => 'TimeField',
                'expected' => DBTime::class,
            ],
            [
                'data' => 123456,
                'source' => TestDataObject::class,
                'fieldName' => 'RandomField',
                'expected' => DBInt::class,
            ],
            [
                'data' => '<body>some text</body>',
                'source' => TestDataObject::class,
                'fieldName' => 'RandomField',
                'expected' => DBText::class,
            ],
            [
                'data' => '12.35',
                'source' => null,
                'fieldName' => 'OverrideCastingHelper',
                'expected' => DBText::class,
            ],
            [
                'data' => 123456,
                'source' => null,
                'fieldName' => 'RandomField',
                'expected' => DBInt::class,
            ],
            [
                'data' => '10:17:36',
                'source' => null,
                'fieldName' => 'TimeField',
                'expected' => DBText::class,
            ],
            [
                'data' => '<body>some text</body>',
                'source' => null,
                'fieldName' => '',
                'expected' => DBText::class,
            ],
            [
                'data' => true,
                'source' => null,
                'fieldName' => '',
                'expected' => DBBoolean::class,
            ],
            [
                'data' => false,
                'source' => null,
                'fieldName' => '',
                'expected' => DBBoolean::class,
            ],
            [
                'data' => 1.234,
                'source' => null,
                'fieldName' => '',
                'expected' => DBFloat::class,
            ],
            [
                'data' => [],
                'source' => null,
                'fieldName' => '',
                'expected' => ArrayList::class,
            ],
            [
                'data' => [1,2,3,4],
                'source' => null,
                'fieldName' => '',
                'expected' => ArrayList::class,
            ],
            [
                'data' => ['one' => 1, 'two' => 2],
                'source' => null,
                'fieldName' => '',
                'expected' => ArrayData::class,
            ],
            [
                'data' => ['one' => 1, 'two' => 2],
                'source' => TestDataObject::class,
                'fieldName' => 'AnyField',
                'expected' => ArrayData::class,
            ],
            [
                'data' => ['one' => 1, 'two' => 2],
                'source' => TestDataObject::class,
                'fieldName' => 'ArrayAsText',
                'expected' => DBText::class,
            ],
        ];
    }

    #[DataProvider('provideCast')]
    public function testCast(mixed $data, ?string $source, string $fieldName, ?string $expected): void
    {
        // Can't instantiate DataObject in a data provider
        if (is_string($source)) {
            $source = new $source();
        }
        $service = new CastingService();
        $value = $service->cast($data, $source, $fieldName);

        // Check the cast object is the correct type
        if ($expected === null) {
            $this->assertNull($value);
        } elseif (is_object($data)) {
            $this->assertSame($data, $value);
        } else {
            $this->assertInstanceOf($expected, $value);
        }

        // Check the value is retained
        if ($value instanceof DBField && !is_object($data)) {
            $this->assertSame($data, $value->getValue());
        }
        if ($value instanceof ArrayData && !is_object($data)) {
            $this->assertSame($data, $value->toMap());
        }
        if ($value instanceof ArrayList && !is_object($data)) {
            $this->assertSame($data, $value->toArray());
        }
    }

    public function testCastStrict(): void
    {
        $service = new CastingService();
        $value = $service->cast(null, strict: true);
        $this->assertInstanceOf(DBText::class, $value);
        $this->assertNull($value->getValue());
    }
}
