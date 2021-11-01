<?php

namespace SilverStripe\Forms\Tests;

use LogicException;
use ReflectionClass;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NullableField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tests\FormFieldTest\TestExtension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Tip;
use SilverStripe\ORM\ValidationResult;

class FormFieldTest extends SapphireTest
{

    protected static $required_extensions = [
        FormField::class => [
            TestExtension::class,
        ],
    ];

    public function testDefaultClasses()
    {
        Config::nest();

        FormField::config()->update(
            'default_classes',
            [
                'class1',
            ]
        );

        $field = new FormField('MyField');

        $this->assertStringContainsString('class1', $field->extraClass(), 'Class list does not contain expected class');

        FormField::config()->update(
            'default_classes',
            [
                'class1',
                'class2',
            ]
        );

        $field = new FormField('MyField');

        $this->assertStringContainsString('class1 class2', $field->extraClass(), 'Class list does not contain expected class');

        FormField::config()->update(
            'default_classes',
            [
                'class3',
            ]
        );

        $field = new FormField('MyField');

        $this->assertStringContainsString('class3', $field->extraClass(), 'Class list does not contain expected class');

        $field->removeExtraClass('class3');

        $this->assertStringNotContainsString('class3', $field->extraClass(), 'Class list contains unexpected class');

        TextField::config()->update(
            'default_classes',
            [
                'textfield-class',
            ]
        );

        $field = new TextField('MyField');

        //check default classes inherit
        $this->assertStringContainsString('class3', $field->extraClass(), 'Class list does not contain inherited class');
        $this->assertStringContainsString('textfield-class', $field->extraClass(), 'Class list does not contain expected class');

        Config::unnest();
    }

    public function testAddExtraClass()
    {
        $field = new FormField('MyField');
        $field->addExtraClass('class1');
        $field->addExtraClass('class2');
        $this->assertStringEndsWith('class1 class2', $field->extraClass());
    }

    public function testRemoveExtraClass()
    {
        $field = new FormField('MyField');
        $field->addExtraClass('class1');
        $field->addExtraClass('class2');
        $this->assertStringEndsWith('class1 class2', $field->extraClass());
        $field->removeExtraClass('class1');
        $this->assertStringEndsWith('class2', $field->extraClass());
    }

    public function testAddManyExtraClasses()
    {
        $field = new FormField('MyField');
        //test we can split by a range of spaces and tabs
        $field->addExtraClass('class1 class2     class3	class4		class5');
        $this->assertStringEndsWith(
            'class1 class2 class3 class4 class5',
            $field->extraClass()
        );
        //test that duplicate classes don't get added
        $field->addExtraClass('class1 class2');
        $this->assertStringEndsWith(
            'class1 class2 class3 class4 class5',
            $field->extraClass()
        );
    }

    public function testRemoveManyExtraClasses()
    {
        $field = new FormField('MyField');
        $field->addExtraClass('class1 class2     class3	class4		class5');
        //test we can remove a single class we just added
        $field->removeExtraClass('class3');
        $this->assertStringEndsWith(
            'class1 class2 class4 class5',
            $field->extraClass()
        );
        //check we can remove many classes at once
        $field->removeExtraClass('class1 class5');
        $this->assertStringEndsWith(
            'class2 class4',
            $field->extraClass()
        );
        //check that removing a dud class is fine
        $field->removeExtraClass('dudClass');
        $this->assertStringEndsWith(
            'class2 class4',
            $field->extraClass()
        );
    }

    public function testAttributes()
    {
        $field = new FormField('MyField');
        $field->setAttribute('foo', 'bar');
        $this->assertEquals('bar', $field->getAttribute('foo'));
        $attrs = $field->getAttributes();
        $this->assertArrayHasKey('foo', $attrs);
        $this->assertEquals('bar', $attrs['foo']);
    }

    public function testAttributesHTML()
    {
        $field = new FormField('MyField');

        $field->setAttribute('foo', 'bar');
        $this->assertStringContainsString('foo="bar"', $field->getAttributesHTML());

        $field->setAttribute('foo', null);
        $this->assertStringNotContainsString('foo=', $field->getAttributesHTML());

        $field->setAttribute('foo', '');
        $this->assertStringNotContainsString('foo=', $field->getAttributesHTML());

        $field->setAttribute('foo', false);
        $this->assertStringNotContainsString('foo=', $field->getAttributesHTML());

        $field->setAttribute('foo', true);
        $this->assertStringContainsString('foo="foo"', $field->getAttributesHTML());

        $field->setAttribute('foo', 'false');
        $this->assertStringContainsString('foo="false"', $field->getAttributesHTML());

        $field->setAttribute('foo', 'true');
        $this->assertStringContainsString('foo="true"', $field->getAttributesHTML());

        $field->setAttribute('foo', 0);
        $this->assertStringContainsString('foo="0"', $field->getAttributesHTML());

        $field->setAttribute('one', 1);
        $field->setAttribute('two', 2);
        $field->setAttribute('three', 3);
        $this->assertStringNotContainsString('one="1"', $field->getAttributesHTML('one', 'two'));
        $this->assertStringNotContainsString('two="2"', $field->getAttributesHTML('one', 'two'));
        $this->assertStringContainsString('three="3"', $field->getAttributesHTML('one', 'two'));
    }

    /**
     * Covering all potential inputs for Convert::raw2xml
     */
    public function escapeHtmlDataProvider()
    {
        return [
            ['<html>'],
            [['<html>']],
            [['<html>' => '<html>']]
        ];
    }

    /**
     * @dataProvider escapeHtmlDataProvider
     **/
    public function testGetAttributesEscapeHtml($value)
    {
        $key = bin2hex(random_bytes(4));

        if (is_scalar($value)) {
            $field = new FormField('<html>', '<html>', '<html>');
            $field->setAttribute($value, $key);
            $html = $field->getAttributesHTML();
            $this->assertFalse(strpos($html, '<html>'));
        }

        $field = new FormField('<html>', '<html>', '<html>');
        $field->setAttribute($key, $value);
        $html = $field->getAttributesHTML();

        $this->assertFalse(strpos($html, '<html>'));
    }

    /**
     * @dataProvider escapeHtmlDataProvider
     */
    public function testDebugEscapeHtml($value)
    {
        $field = new FormField('<html>', '<html>', '<html>');
        $field->setAttribute('<html>', $value);
        $field->setMessage('<html>', null, ValidationResult::CAST_HTML);

        $html = $field->debug();

        $this->assertFalse(strpos($html, '<html>'));
    }

    public function testReadonly()
    {
        $field = new FormField('MyField');
        $field->setReadonly(true);
        $this->assertStringContainsString('readonly="readonly"', $field->getAttributesHTML());
        $field->setReadonly(false);
        $this->assertStringNotContainsString('readonly="readonly"', $field->getAttributesHTML());
    }

    public function testDisabled()
    {
        $field = new FormField('MyField');
        $field->setDisabled(true);
        $this->assertStringContainsString('disabled="disabled"', $field->getAttributesHTML());
        $field->setDisabled(false);
        $this->assertStringNotContainsString('disabled="disabled"', $field->getAttributesHTML());
    }

    public function testEveryFieldTransformsReadonlyAsClone()
    {
        $fieldClasses = ClassInfo::subclassesFor(FormField::class);
        foreach ($fieldClasses as $fieldClass) {
            $reflectionClass = new ReflectionClass($fieldClass);
            if (!$reflectionClass->isInstantiable()) {
                continue;
            }
            $constructor = $reflectionClass->getMethod('__construct');
            if ($constructor->getNumberOfRequiredParameters() > 1) {
                continue;
            }
            if (is_a($fieldClass, CompositeField::class, true)) {
                continue;
            }

            $fieldName = $reflectionClass->getShortName() . '_instance';
            /** @var FormField $instance */
            if ($fieldClass = NullableField::class) {
                $instance = new $fieldClass(new TextField($fieldName));
            } else {
                $instance = new $fieldClass($fieldName);
            }
            $isReadonlyBefore = $instance->isReadonly();
            $readonlyInstance = $instance->performReadonlyTransformation();
            $this->assertEquals(
                $isReadonlyBefore,
                $instance->isReadonly(),
                "FormField class {$fieldClass} retains its readonly state after calling performReadonlyTransformation()"
            );
            $this->assertTrue(
                $readonlyInstance->isReadonly(),
                "FormField class {$fieldClass} returns a valid readonly representation as of isReadonly()"
            );
            $this->assertNotSame(
                $readonlyInstance,
                $instance,
                "FormField class {$fieldClass} returns a valid cloned readonly representation"
            );
        }
    }

    public function testEveryFieldTransformsDisabledAsClone()
    {
        $fieldClasses = ClassInfo::subclassesFor(FormField::class);
        foreach ($fieldClasses as $fieldClass) {
            $reflectionClass = new ReflectionClass($fieldClass);
            if (!$reflectionClass->isInstantiable()) {
                continue;
            }
            $constructor = $reflectionClass->getMethod('__construct');
            if ($constructor->getNumberOfRequiredParameters() > 1) {
                continue;
            }
            if (is_a($fieldClass, CompositeField::class, true)) {
                continue;
            }

            $fieldName = $reflectionClass->getShortName() . '_instance';
            /** @var FormField $instance */
            if ($fieldClass = NullableField::class) {
                $instance = new $fieldClass(new TextField($fieldName));
            } else {
                $instance = new $fieldClass($fieldName);
            }

            $isDisabledBefore = $instance->isDisabled();
            $disabledInstance = $instance->performDisabledTransformation();
            $this->assertEquals(
                $isDisabledBefore,
                $instance->isDisabled(),
                "FormField class {$fieldClass} retains its disabled state after calling performDisabledTransformation()"
            );
            $this->assertTrue(
                $disabledInstance->isDisabled(),
                "FormField class {$fieldClass} returns a valid disabled representation as of isDisabled()"
            );
            $this->assertNotSame(
                $disabledInstance,
                $instance,
                "FormField class {$fieldClass} returns a valid cloned disabled representation"
            );
        }
    }

    public function testUpdateAttributes()
    {
        $field = new FormField('MyField');
        $this->assertArrayHasKey('extended', $field->getAttributes());
    }

    public function testSetSchemaComponent()
    {
        $field = new FormField('MyField');
        $field = $field->setSchemaComponent('MyComponent');
        $component = $field->getSchemaComponent();
        $this->assertEquals('MyComponent', $component);
    }

    public function testGetSchemaDataDefaults()
    {
        $field = new FormField('MyField');
        $schema = $field->getSchemaDataDefaults();
        $this->assertIsArray($schema);
    }

    public function testGetSchemaDataDefaultsTitleTip()
    {
        $field = new FormField('MyField');
        $schema = $field->getSchemaDataDefaults();
        $this->assertFalse(array_key_exists('titleTip', $schema));
        $field->setTitleTip(new Tip('Test tip'));
        $schema = $field->getSchemaDataDefaults();
        $this->assertSame('Test tip', $schema['titleTip']['content']);
    }

    public function testGetSchemaData()
    {
        $field = new FormField('MyField');
        $schema = $field->getSchemaData();
        $this->assertEquals('MyField', $schema['name']);

        // Make sure the schema data is up-to-date with object properties.
        $field->setName('UpdatedField');
        $schema = $field->getSchemaData();
        $this->assertEquals($field->getName(), $schema['name']);
    }

    public function testSetSchemaData()
    {
        $field = new FormField('MyField');

        // Make sure the user can update values.
        $field->setSchemaData(['name' => 'MyUpdatedField']);
        $schema = $field->getSchemaData();
        $this->assertEquals($schema['name'], 'MyUpdatedField');

        // Make user the user can't define custom keys on the schema.
        $field = $field->setSchemaData(['myCustomKey' => 'yolo']);
        $schema = $field->getSchemaData();
        $this->assertEquals(array_key_exists('myCustomKey', $schema), false);
    }

    public function testGetSchemaState()
    {
        $field = new FormField('MyField');
        $field->setValue('My value');
        $schema = $field->getSchemaState();
        $this->assertEquals('My value', $schema['value']);
    }

    public function testSetSchemaState()
    {
        $field = new FormField('MyField');

        // Make sure the user can update values.
        $field->setSchemaState(['value' => 'My custom value']);
        $schema = $field->getSchemaState();
        $this->assertEquals($schema['value'], 'My custom value');

        // Make user the user can't define custom keys on the schema.
        $field->setSchemaState(['myCustomKey' => 'yolo']);
        $schema = $field->getSchemaState();
        $this->assertEquals(array_key_exists('myCustomKey', $schema), false);
    }

    public function testGetSchemaStateWithFormValidation()
    {
        $field = new FormField('MyField', 'My Field');
        $validator = new RequiredFields('MyField');
        $form = new Form(null, 'TestForm', new FieldList($field), new FieldList(), $validator);
        $form->validationResult();
        $schema = $field->getSchemaState();
        $this->assertEquals(
            '"My Field" is required',
            $schema['message']['value']
        );
    }

    public function testHasClass()
    {
        $field = new FormField('Test');
        $field->addExtraClass('foo BAr cool-banana');

        $this->assertTrue($field->hasClass('foo'));
        $this->assertTrue($field->hasClass('bAr'));
        $this->assertFalse($field->hasClass('banana'));
        $this->assertTrue($field->hasClass('cool-BAnana'));
    }

    public function testLinkWithForm()
    {
        $field = new FormField('Test');
        $form = new Form(null, 'Test', new FieldList, new FieldList);
        $form->setFormAction('foo');
        $field->setForm($form);
        $this->assertSame('foo/field/Test/bar', $field->Link('bar'));
    }

    public function testLinkWithoutForm()
    {
        $this->expectException(LogicException::class);
        $field = new FormField('Test');
        $field->Link('bar');
    }

    /**
     * @param string $name
     * @param string $expected
     * @dataProvider nameToLabelProvider
     */
    public function testNameToLabel($name, $expected)
    {
        $this->assertSame($expected, FormField::name_to_label($name));
    }

    /**
     * @return array[]
     */
    public function nameToLabelProvider()
    {
        return [
            ['TotalAmount', 'Total amount'],
            ['Organisation.ZipCode', 'Organisation zip code'],
            ['Organisation.zipCode', 'Organisation zip code'],
            ['FooBarBaz', 'Foo bar baz'],
            ['URLSegment', 'URL segment'],
            ['ONLYCAPS', 'ONLYCAPS'],
            ['onlylower', 'Onlylower'],
            ['SpecialURL', 'Special URL'],
        ];
    }

    public function testGetSetTitleTip()
    {
        $field = new FormField('MyField');
        $this->assertNull($field->getTitleTip());
        $field->setTitleTip(new Tip('Test tip'));
        $this->assertInstanceOf(Tip::class, $field->getTitleTip());
        $this->assertSame('Test tip', $field->getTitleTip()->getMessage());
    }
}
