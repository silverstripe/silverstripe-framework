<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\SearchableDropdownField;
use SilverStripe\Forms\Tests\FormTest\Team;
use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Forms\HiddenField;
use stdClass;
use SilverStripe\Forms\Form;
use PHPUnit\Framework\Attributes\DataProvider;

class SearchableDropdownTraitTest extends SapphireTest
{
    protected static $fixture_file = 'SearchableDropdownTraitTest.yml';

    protected static $extra_dataobjects = [
        Team::class,
    ];

    public function testGetSchemaDataType(): void
    {
        $singleField = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $multiField = new SearchableMultiDropdownField('MyField', 'MyField', Team::get());
        $this->assertSame($singleField->getSchemaDataType(), FormField::SCHEMA_DATA_TYPE_SINGLESELECT);
        $this->assertSame($multiField->getSchemaDataType(), FormField::SCHEMA_DATA_TYPE_MULTISELECT);
    }

    public function testSearch(): void
    {
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $request = new HTTPRequest('GET', 'someurl', ['term' => 'Team']);
        $request->addHeader('X-SecurityID', SecurityToken::getSecurityID());
        $response = $field->search($request);
        $this->assertSame(200, $response->getStatusCode());
        $actual = json_decode($response->getBody(), true);
        $ids = Team::get()->column('ID');
        $names = Team::get()->column('Name');
        $expected = [
            ['value' => $ids[0], 'label' => $names[0]],
            ['value' => $ids[1], 'label' => $names[1]],
            ['value' => $ids[2], 'label' => $names[2]],
        ];
        $this->assertSame($expected, $actual);
    }

    public function testSearchNoCsrfToken(): void
    {
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $request = new HTTPRequest('GET', 'someurl', ['term' => 'Team']);
        $response = $field->search($request);
        $this->assertSame(400, $response->getStatusCode());
        $actual = json_decode($response->getBody(), true);
        $expected = ['message' => 'Invalid CSRF token'];
        $this->assertSame($expected, $actual);
    }

    public function testPlaceholder(): void
    {
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $this->assertSame('Select or type to search...', $field->getPlaceholder());
        $field->setIsSearchable(false);
        $this->assertSame('Select...', $field->getPlaceholder());
        $field->setIsLazyLoaded(true);
        $this->assertSame('Type to search...', $field->getPlaceholder());
        $field->setEmptyString('My empty string');
        $this->assertSame('My empty string', $field->getPlaceholder());
        $field->setPlaceholder('My placeholder');
        $this->assertSame('My placeholder', $field->getPlaceholder());
    }

    public function testSeachContext(): void
    {
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $team = Team::get()->first();
        // assert fallback is the default search context
        $this->assertSame(
            $team->getDefaultSearchContext()->getFields()->dataFieldNames(),
            $field->getSearchContext()->getFields()->dataFieldNames()
        );
        // assert setting a custom search context should override the default
        $searchContext = new SearchContext(Team::class, new FieldList(new HiddenField('lorem')));
        $field->setSearchContext($searchContext);
        $this->assertSame(
            $searchContext->getFields()->dataFieldNames(),
            $field->getSearchContext()->getFields()->dataFieldNames()
        );
    }

    public function testLabelField(): void
    {
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        // will use the default value of 'Title' for label field
        $this->assertSame('Title', $field->getLabelField());
        // can override the default
        $field->setLabelField('Something');
        $this->assertSame('Something', $field->getLabelField());
    }

    #[DataProvider('provideGetValueArray')]
    public function testGetValueArray(mixed $value, string|array $expected): void
    {
        if ($value === '<DataListValue>') {
            $value = Team::get();
            $ids = Team::get()->column('ID');
            $expected = [$ids[0], $ids[1], $ids[2]];
        } elseif ($value === '<DataObjectValue>') {
            $value = Team::get()->first();
            $expected = [$value->ID];
        }
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $field->setValue($value);
        $this->assertSame($expected, $field->getValueArray());
    }

    public static function provideGetValueArray(): array
    {
        return [
            'empty' => [
                'value' => '',
                'expected' => [],
            ],
            'array single form builder' => [
                'value' => ['label' => 'MyTitle15', 'value' => '10', 'selected' => false],
                'expected' => [10],
            ],
            'array multi form builder' => [
                'value' => [
                    ['label' => 'MyTitle10', 'value' => '10', 'selected' => true],
                    ['label' => 'MyTitle15', 'value' => '15', 'selected' => false],
                ],
                'expected' => [10, 15],
            ],
            'string int' => [
                'value' => '3',
                'expected' => [3],
            ],
            'zero string' => [
                'value' => '0',
                'expected' => [],
            ],
            'datalist' => [
                'value' => '<DataListValue>',
                'expected' => '<DataListExpected>',
            ],
            'dataobject' => [
                'value' => '<DataObjectValue>',
                'expected' => '<DataObjectExpected>',
            ],
            'something else' => [
                'value' => new stdClass(),
                'expected' => [],
            ],
            'negative int' => [
                'value' => -1,
                'expected' => [],
            ],
            'negative string int' => [
                'value' => '-1',
                'expected' => [],
            ],
        ];
    }

    public function testGetSchemaDataDefaults(): void
    {
        // setting a form is required for Link() which is called for 'optionUrl'
        $form = new Form();
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $field->setHasEmptyDefault(false);
        $field->setForm($form);
        $team = Team::get()->first();
        $schema = $field->getSchemaDataDefaults();
        $this->assertSame('MyField', $schema['name']);
        $this->assertSame(['value' => $team->ID, 'label' => $team->Name, 'selected' => false], $schema['value']);
        $this->assertFalse($schema['multi']);
        $this->assertTrue(is_array($schema['options']));
        $this->assertFalse(array_key_exists('optionUrl', $schema));
        $this->assertFalse($schema['disabled']);
        // lazyload changes options/optionUrl
        $field->setIsLazyLoaded(true);
        $schema = $field->getSchemaDataDefaults();
        $this->assertFalse(array_key_exists('options', $schema));
        $this->assertSame('field/MyField/search', $schema['optionUrl']);
        // disabled
        $field->setReadonly(true);
        $schema = $field->getSchemaDataDefaults();
        $this->assertTrue($schema['disabled']);
        // multi field name
        $field = new SearchableMultiDropdownField('MyField', 'MyField', Team::get());
        $field->setForm($form);
        $schema = $field->getSchemaDataDefaults();
        $this->assertSame('MyField[]', $schema['name']);
        $this->assertTrue($schema['multi']);
        // accessors
        $field = new SearchableDropdownField('MyField', 'MyField', Team::get());
        $field->setForm($form);
        $schema = $field->getSchemaDataDefaults();
        $this->assertFalse($schema['lazyLoad']);
        $this->assertTrue($schema['clearable']);
        $this->assertSame('Select or type to search...', $schema['placeholder']);
        $this->assertTrue($schema['searchable']);
        $field->setIsLazyLoaded(true);
        $field->setIsClearable(false);
        $field->setPlaceholder('My placeholder');
        $field->setIsSearchable(false);
        $schema = $field->getSchemaDataDefaults();
        $this->assertTrue($schema['lazyLoad']);
        $this->assertFalse($schema['clearable']);
        $this->assertSame('My placeholder', $schema['placeholder']);
        $this->assertFalse($schema['searchable']);
    }

    public static function provideLazyLoadedDoesntCallGetSource()
    {
        $methodsToCall = [
            'Field',
            'getSchemaStateDefaults',
            'getSchemaState',
            'getSchemaDataDefaults',
            'getSchemaData',
        ];
        $classes = [
            SearchableMultiDropdownField::class,
            SearchableDropdownField::class,
        ];
        $scenarios = [];
        foreach ($classes as $class) {
            foreach ($methodsToCall as $method) {
                $scenarios[] = [
                    'fieldClass' => $class,
                    'methodToCall' => $method,
                ];
            }
        }
        return $scenarios;
    }

    #[DataProvider('provideLazyLoadedDoesntCallGetSource')]
    public function testLazyLoadedDoesntCallGetSource(string $fieldClass, string $methodToCall)
    {
        // Some methods aren't shared between the two form fields.
        if (!ClassInfo::hasMethod($fieldClass, $methodToCall)) {
            $this->markTestSkipped("$fieldClass doesn't have method $methodToCall - skipping");
        }
        // We have to disable the constructor because it ends up calling a static method, and we can't call static methods on mocks.
        $mockField = $this->getMockBuilder($fieldClass)->onlyMethods(['getSource'])->disableOriginalConstructor()->getMock();
        $mockField->expects($this->never())->method('getSource');
        $mockField->setIsLazyLoaded(true);
        $mockField->setSource(Team::get());
        $mockField->setForm(new Form());
        $mockField->$methodToCall();
    }
}
