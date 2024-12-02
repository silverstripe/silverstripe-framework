<?php

namespace SilverStripe\Forms\Tests;

use Composer\InstalledVersions;
use LogicException;
use ReflectionClass;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridState;
use SilverStripe\Forms\NullableField;
use SilverStripe\Forms\PopoverField;
use SilverStripe\Forms\PrintableTransformation_TabSet;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\SelectionGroup;
use SilverStripe\Forms\SelectionGroup_Item;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\Tests\FormFieldTest\FieldValidationExtension;
use SilverStripe\Forms\Tests\FormFieldTest\TestExtension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Tip;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\TreeDropdownField_Readonly;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionCheckboxSetField;
use SilverStripe\Security\PermissionCheckboxSetField_Readonly;
use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\Forms\SearchableDropdownField;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\FieldsValidator;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField_Readonly;
use SilverStripe\VersionedAdmin\Forms\DiffField;
use SilverStripe\Subsites\Forms\WildcardDomainField;
use InvalidArgumentException;
use Exception;
use ReflectionMethod;
use SilverStripe\Core\Validation\FieldValidation\CompositeFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\DateFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\EmailFieldValidator;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\UrlFieldValidator;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\CurrencyField_Disabled;
use SilverStripe\Forms\CurrencyField_Readonly;
use SilverStripe\Forms\DatalessField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DateField_Disabled;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\GroupedDropdownField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Forms\LabelField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\LookupField;
use SilverStripe\Forms\MoneyField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\SearchableLookupField;
use SilverStripe\Forms\SingleLookupField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TimeField;
use SilverStripe\Forms\TimeField_Readonly;
use SilverStripe\Forms\TreeMultiselectField;
use SilverStripe\Forms\TreeMultiselectField_Readonly;
use SilverStripe\Forms\UrlField;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField_Readonly;
use SilverStripe\Core\Validation\FieldValidation\MultiOptionFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\NumericFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\OptionFieldValidator;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\SegmentField;
use SilverStripe\Core\Validation\FieldValidation\TimeFieldValidator;
use SilverStripe\Core\Validation\FieldValidation\DatetimeFieldValidator;

class FormFieldTest extends SapphireTest
{
    protected static $required_extensions = [
        FormField::class => [
            TestExtension::class,
            FieldValidationExtension::class,
        ],
    ];

    public function testDefaultClasses()
    {
        Config::nest();

        FormField::config()->merge(
            'default_classes',
            [
                'class1',
            ]
        );

        $field = new FormField('MyField');

        $this->assertStringContainsString('class1', $field->extraClass(), 'Class list does not contain expected class');

        FormField::config()->merge(
            'default_classes',
            [
                'class1',
                'class2',
            ]
        );

        $field = new FormField('MyField');

        $this->assertStringContainsString('class1 class2', $field->extraClass(), 'Class list does not contain expected class');

        FormField::config()->merge(
            'default_classes',
            [
                'class3',
            ]
        );

        $field = new FormField('MyField');

        $this->assertStringContainsString('class3', $field->extraClass(), 'Class list does not contain expected class');

        $field->removeExtraClass('class3');

        $this->assertStringNotContainsString('class3', $field->extraClass(), 'Class list contains unexpected class');

        TextField::config()->merge(
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

    public function testHasExtraClass()
    {
        $field = new FormField('MyField');
        $field->addExtraClass('class1');
        $field->addExtraClass('class2');
        $this->assertTrue($field->hasExtraClass('class1'));
        $this->assertTrue($field->hasExtraClass('class2'));
        $this->assertTrue($field->hasExtraClass('class1 class2'));
        $this->assertTrue($field->hasExtraClass('class2 class1'));
        $this->assertFalse($field->hasExtraClass('class3'));
        $this->assertFalse($field->hasExtraClass('class2 class3'));
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
    public static function escapeHtmlDataProvider()
    {
        return [
            ['<html>'],
            [['<html>']],
            [['<html>' => '<html>']]
        ];
    }

    /**
     **/
    #[DataProvider('escapeHtmlDataProvider')]
    public function testGetAttributesEscapeHtml($value)
    {
        $key = bin2hex(random_bytes(4));

        if (is_scalar($value)) {
            $field = new FormField('<html>', '<html>', '<html>');
            $field->setAttribute($value, $key);
            $html = $field->getAttributesHTML();
            $this->assertFalse(strpos($html ?? '', '<html>'));
        }

        $field = new FormField('<html>', '<html>', '<html>');
        $field->setAttribute($key, $value);
        $html = $field->getAttributesHTML();

        $this->assertFalse(strpos($html ?? '', '<html>'));
    }

    #[DataProvider('escapeHtmlDataProvider')]
    public function testDebugEscapeHtml($value)
    {
        $field = new FormField('<html>', '<html>', '<html>');
        $field->setAttribute('<html>', $value);
        $field->setMessage('<html>', null, ValidationResult::CAST_HTML);

        $html = $field->debug();

        $this->assertFalse(strpos($html ?? '', '<html>'));
    }

    public function testReadonly()
    {
        $field = new FormField('MyField');
        $field->setReadonly(true);
        $this->assertStringContainsString('readonly="readonly"', $field->getAttributesHTML());
        $field->setReadonly(false);
        $this->assertStringNotContainsString('readonly="readonly"', $field->getAttributesHTML());
    }

    public function testReadonlyPreservesExtraClass()
    {
        $field = new FormField('MyField');
        $field->addExtraClass('myextraclass1')->addExtraClass('myextraclass2');
        $field->setReadonly(true);
        $this->assertStringContainsString('myextraclass1 myextraclass2', $field->getAttributesHTML());
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
        $this->assertFalse(array_key_exists('titleTip', $schema ?? []));
        $field->setTitleTip(new Tip('Test tip'));
        $schema = $field->getSchemaDataDefaults();
        $this->assertSame('Test tip', $schema['titleTip']['content']);
    }

    public function testGetSchemaDataDefaultsAttributes()
    {
        $field = new FormField('MyField');
        $field->setAttribute('foo', 'bar');
        $this->assertEquals('bar', $field->getAttribute('foo'));
        $schema = $field->getSchemaDataDefaults();
        $this->assertArrayHasKey('foo', $schema['attributes']);
        $this->assertEquals('bar', $schema['attributes']['foo']);
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
        $this->assertEquals(array_key_exists('myCustomKey', $schema ?? []), false);
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
        $this->assertEquals(array_key_exists('myCustomKey', $schema ?? []), false);
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

    public function testValidationExtensionHooks()
    {
        /** @var TextField|FieldValidationExtension $field */
        $field = new TextField('Test');
        $field->setMaxLength(5);
        $form = new Form(null, 'test', new FieldList($field), new FieldList(), new FieldsValidator());
        $form->disableSecurityToken();

        $field->setValue('IAmLongerThan5Characters');
        $result = $field->validate();
        $this->assertFalse($result->isValid());

        // Call extension method in FieldValidationExtension
        $field->setExcludeFromValidation(true);
        $result = $field->validate();
        $this->assertTrue($result->isValid());

        // Call extension methods in FieldValidationExtension
        $field->setValue('1234');
        $field->setExcludeFromValidation(false);
        $field->setTriggerTestValidationError(true);
        $result = $field->validate();
        $this->assertFalse($result->isValid());

        // Ensure messages set via updateValidate() propagate through after form validation
        $result = $form->validationResult();
        $state = $field->getSchemaState();
        $this->assertEquals('A test error message', $state['message']['value']);
    }

    public function testValidationExtensionHooksAreCalledOnFormFieldSubclasses()
    {
        // Can't use a dataProvider for this as dataProviders are fetched very early by phpunit,
        // and the ClassManifest isn't ready then
        $formFieldClasses = ClassInfo::subclassesFor(FormField::class, false);
        foreach ($formFieldClasses as $formFieldClass) {
            $reflection = new ReflectionClass($formFieldClass);
            // Skip abstract classes, like MultiSelectField, and fields that only exist for unit tests
            if ($reflection->isAbstract() || is_a($formFieldClass, TestOnly::class, true)) {
                continue;
            }

            // Create appropriate constructor arguments for the form field class. These don't have to be offer realistic
            // data, they just need to ensure we can construct the field and call ->validate() on it
            switch ($formFieldClass) {
                //
                // Fields in framework with specific argument requirements
                //
                case NullableField::class:
                case CompositeField::class:
                case FieldGroup::class:
                case PopoverField::class:
                    $args = [TextField::create('Test2')];
                    break;
                case SelectionGroup_Item::class:
                    $args = ['Test', [TextField::create('Test2')]];
                    break;
                case ToggleCompositeField::class:
                    $args = ['Test', 'Test', TextField::create('Test2')];
                    break;
                case PrintableTransformation_TabSet::class:
                    $args = [Tab::create('TestTab', 'Testtab', TextField::create('Test2'))];
                    break;
                case TreeDropdownField::class:
                case TreeDropdownField_Readonly::class:
                    $args = ['Test', 'Test', Group::class];
                    break;
                case PermissionCheckboxSetField::class:
                case PermissionCheckboxSetField_Readonly::class:
                    $args = ['Test', 'Test', Permission::class, 'Test'];
                    break;
                case SelectionGroup::class:
                    $args = ['Test', []];
                    break;
                case GridField_FormAction::class:
                    $args = [GridField::create('GF'), 'Test', 'Test label', 'Test action name', []];
                    break;
                case GridState::class:
                    $args = [GridField::create('GF')];
                    break;
                case SearchableDropdownField::class:
                case SearchableMultiDropdownField::class:
                    $args = ['Test', 'Test', Group::get()];
                    break;
                //
                // Fields from other modules included in the kitchensink recipe
                //
                case \SilverStripe\Blog\Admin\GridFieldFormAction::class:
                    $args = [GridField::create('GF'), 'Test', 'Test label', 'Test action name', []];
                    break;
                case \SilverStripe\Blog\Forms\BlogAdminSidebar::class:
                    $args = [TextField::create('Test2')];
                    break;
                case \SilverStripe\CKANRegistry\Forms\PresentedOptionsField::class:
                    $args = ['Test', \SilverStripe\CKANRegistry\Model\Resource::create()];
                    break;
                case \SilverStripe\DocumentConverter\SettingsField::class:
                    $args = [];
                    break;
                case \DNADesign\Elemental\Forms\ElementalAreaField::class:
                    $args = ['Test', \DNADesign\Elemental\Models\ElementalArea::create(), []];
                    break;
                case \SilverStripe\MFA\FormField\RegisteredMFAMethodListField::class:
                    $args = ['Test', 'Test label', 1];
                    break;
                case \SilverStripe\Subsites\Forms\SubsitesTreeDropdownField::class:
                    $args = ['Test', 'Test', Group::class];
                    break;
                case \SilverStripe\UserForms\FormField\UserFormsCompositeField::class:
                case \SilverStripe\UserForms\FormField\UserFormsGroupField::class:
                case \SilverStripe\UserForms\FormField\UserFormsStepField::class:
                    $args = [TextField::create('Test2')];
                    break;
                case \Symbiote\AdvancedWorkflow\FormFields\WorkflowField::class:
                    $args = ['Test', 'Test label', \Symbiote\AdvancedWorkflow\DataObjects\WorkflowDefinition::create()];
                    break;
                //
                // Default arguments, this covers most simple form fields
                //
                default:
                    $args = ['Test', 'Test label'];
            }
            $field = Injector::inst()->createWithArgs($formFieldClass, $args);
            if ($formFieldClass === ConfirmedPasswordField::class) {
                /** @var ConfirmedPasswordField $field */
                $field->setCanBeEmpty(true);
            } elseif ($formFieldClass === DiffField::class) {
                /** @var DiffField $field */
                $field->setComparisonField(new TextField('Test2'));
            } elseif ($formFieldClass === WildcardDomainField::class) {
                $field->setValue('*');
            }
            $this->assertTrue($field->validate()->isValid(), "$formFieldClass should be valid");
        }
    }

    public static function provideSetSubmittedValue(): array
    {
        return [
            'int-1' => [
                'value' => 1,
                'expected' => 1,
            ],
            'int-0' => [
                'value' => 0,
                'expected' => 0,
            ],
            'string-int-1' => [
                'value' => '1',
                'expected' => '1',
            ],
            'string-int-0' => [
                'value' => '0',
                'expected' => '0',
            ],
            'blank-string' => [
                'value' => '',
                'expected' => null,
            ],
            'string' => [
                'value' => 'fish',
                'expected' => 'fish',
            ],
            'array-string' => [
                'value' => ['fish'],
                'expected' => ['fish'],
            ],
            'array-int' => [
                'value' => [1],
                'expected' => [1],
            ],
            'array-empty' => [
                'value' => [],
                'expected' => [],
            ],
            'bool-true' => [
                'value' => true,
                'expected' => true,
            ],
            'bool-false' => [
                'value' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideSetSubmittedValue')]
    public function testSetSubmittedValue(mixed $value, mixed $expected): void
    {
        $field = new FormField('MyField');
        $field->setSubmittedValue('My value');
        $this->assertEquals('My value', $field->getValue());
    }

    #[DataProvider('provideSetSubmittedValue')]
    public function testSetValue(mixed $value, mixed $expected): void
    {
        // Uses the same DataProvider as testSetSubmittedValue()
        // Explicity expect that setValue() does not alter the value
        $expected = $value;
        $field = new FormField('MyField');
        $field->setValue('My value');
        $this->assertEquals('My value', $field->getValue());
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
     */
    #[DataProvider('nameToLabelProvider')]
    public function testNameToLabel($name, $expected)
    {
        $this->assertSame($expected, FormField::name_to_label($name));
    }

    /**
     * @return array[]
     */
    public static function nameToLabelProvider()
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

    public function testCastedCopy()
    {
        $field = new FormField('MyField');
        $this->assertTrue(is_a($field->castedCopy(TextField::class), TextField::class));
        $this->assertTrue(is_a($field->castedCopy(CompositeField::class), CompositeField::class));
    }

    public function testUpdateValidateHookCalled(): void
    {
        foreach ($this->getFormFieldClasses() as $class) {
            $field = $this->instantiateFormField($class);
            $field->setTriggerTestValidationError(true);
            $messages = $field->validate()->getMessages();
            $found = false;
            foreach ($messages as $message) {
                if ($message['message'] === 'A test error message') {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, $class);
        }
    }

    public function testFieldValidatorConfig(): void
    {
        $expectedFieldValidators = [
            CheckboxField::class => [],
            CheckboxField_Readonly::class => [],
            CheckboxSetField::class => [
                MultiOptionFieldValidator::class,
            ],
            CompositeField::class => [
                CompositeFieldValidator::class,
            ],
            ConfirmedPasswordField::class => [],
            CurrencyField::class => [
                StringFieldValidator::class,
            ],
            CurrencyField_Disabled::class => [
                StringFieldValidator::class,
            ],
            CurrencyField_Readonly::class => [],
            DatalessField::class => [],
            DateField::class => [
                StringFieldValidator::class,
                DateFieldValidator::class,
            ],
            DateField_Disabled::class => [
                StringFieldValidator::class,
                DateFieldValidator::class,
            ],
            DatetimeField::class => [
                StringFieldValidator::class,
                DatetimeFieldValidator::class,
            ],
            DropdownField::class => [
                OptionFieldValidator::class,
            ],
            EmailField::class => [
                StringFieldValidator::class,
                EmailFieldValidator::class,
            ],
            FileField::class => [],
            FormAction::class => [],
            FormField::class => [],
            FieldGroup::class => [
                CompositeFieldValidator::class,
            ],
            GridField::class => [],
            GridField_FormAction::class => [],
            GridState::class => [],
            GroupedDropdownField::class => [
                OptionFieldValidator::class,
            ],
            HeaderField::class => [],
            HiddenField::class => [],
            HTMLEditorField::class => [
                StringFieldValidator::class,
            ],
            HTMLEditorField_Readonly::class => [],
            HTMLReadonlyField::class => [],
            LabelField::class => [],
            ListboxField::class => [
                MultiOptionFieldValidator::class,
            ],
            LiteralField::class => [],
            LookupField::class => [],
            MoneyField::class => [
                CompositeFieldValidator::class,
            ],
            NullableField::class => [],
            NumericField::class => [
                StringFieldValidator::class,
                NumericFieldValidator::class,
            ],
            OptionsetField::class => [
                OptionFieldValidator::class,
            ],
            PasswordField::class => [
                StringFieldValidator::class,
            ],
            PopoverField::class => [
                CompositeFieldValidator::class,
            ],
            PrintableTransformation_TabSet::class => [
                CompositeFieldValidator::class,
            ],
            ReadonlyField::class => [],
            SearchableDropdownField::class => [
                OptionFieldValidator::class,
            ],
            SearchableLookupField::class => [],
            SearchableMultiDropdownField::class => [
                MultiOptionFieldValidator::class,
            ],
            SelectionGroup::class => [
                CompositeFieldValidator::class,
            ],
            SelectionGroup_Item::class => [
                CompositeFieldValidator::class,
            ],
            SingleLookupField::class => [],
            Tab::class => [
                CompositeFieldValidator::class,
            ],
            TabSet::class => [
                CompositeFieldValidator::class,
            ],
            TextareaField::class => [
                StringFieldValidator::class,
            ],
            TextField::class => [
                StringFieldValidator::class,
            ],
            TimeField::class => [
                StringFieldValidator::class,
                TimeFieldValidator::class,
            ],
            TimeField_Readonly::class => [
                StringFieldValidator::class,
                TimeFieldValidator::class,
            ],
            ToggleCompositeField::class => [
                CompositeFieldValidator::class,
            ],
            TreeDropdownField::class => [],
            TreeDropdownField_Readonly::class => [],
            TreeMultiselectField::class => [],
            TreeMultiselectField_Readonly::class => [],
            UrlField::class => [
                StringFieldValidator::class,
            ],
        ];
        if (InstalledVersions::isInstalled('silverstripe/segment-field')) {
            $expectedFieldValidators[SegmentField::class] = [
                StringFieldValidator::class,
            ];
        }
        $count = 0;
        foreach ($this->getFormFieldClasses() as $class) {
            if (!array_key_exists($class, $expectedFieldValidators)) {
                throw new Exception("No field validator config found for $class");
            }
            $expected = $expectedFieldValidators[$class];
            $field = $this->instantiateFormField($class);
            $method = new ReflectionMethod($class, 'getFieldValidators');
            $method->setAccessible(true);
            $config = $method->invoke($field);
            $actual = array_map('get_class', $config);
            $this->assertSame($expected, $actual, $class);
            $count++;
        }
        // Assert that we have tested all classes e.g. namespace wasn't changed, no new classes were added
        // that haven't been tested
        $this->assertSame(count($expectedFieldValidators), $count);
    }

    private function instantiateFormField(string $class): FormField
    {
        $args = ['MyField'];
        if (is_a($class, NullableField::class, true)) {
            $args = [new TextField('MyField')];
        } elseif (is_a($class, SearchableDropdownField::class, true)) {
            $args = ['MyField', 'MyField', new DataList(Group::class)];
        } elseif (is_a($class, SearchableMultiDropdownField::class, true)) {
            $args = ['MyField', 'MyField', new DataList(Group::class)];
        } elseif (is_a($class, TreeDropdownField::class, true)) {
            $args = ['MyField', 'MyField', Group::class];
        } elseif (is_a($class, SelectionGroup::class, true)) {
            $args = ['MyField', []];
        } elseif (is_a($class, PrintableTransformation_TabSet::class, true)) {
            $args = [[]];
        } elseif (is_a($class, Tab::class, true)) {
            $args = ['MyField'];
        } elseif (is_a($class, TabSet::class, true)) {
            $args = ['MyField'];
        } elseif (is_a($class, ToggleCompositeField::class, true)) {
            $args = ['MyField', 'MyField', []];
        } elseif (is_a($class, CompositeField::class, true)) {
            $args = [null];
        } elseif (is_a($class, LiteralField::class, true)) {
            $args = ['MyField', ''];
        } elseif (is_a($class, GridField_FormAction::class, true)) {
            $args = [new GridField('MyGridField'), 'MyField', 'MyTitle', 'MyAction', []];
        } elseif (is_a($class, GridState::class, true)) {
            $args = [new GridField('MyGridField')];
        }
        return new $class(...$args);
    }

    private function getFormFieldClasses(): array
    {
        $classes = [];
        foreach (ClassInfo::subclassesFor(FormField::class) as $class) {
            if (is_a($class, TestOnly::class, true)) {
                continue;
            }
            if (!str_starts_with($class, 'SilverStripe\Forms')) {
                continue;
            }
            $reflector = new ReflectionClass($class);
            if ($reflector->isAbstract()) {
                continue;
            }
            $classes[] = $class;
        }
        return $classes;
    }
}
