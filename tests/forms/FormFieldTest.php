<?php
/**
 * @package framework
 * @subpackage tests
 */
class FormFieldTest extends SapphireTest {

	protected $requiredExtensions = array(
		'FormField' => array('FormFieldTest_Extension')
	);

	public function testDefaultClasses() {
		Config::nest();

		Config::inst()->update('FormField', 'default_classes', array(
			'class1',
		));

		$field = new FormField('MyField');

		$this->assertContains('class1', $field->extraClass(), 'Class list does not contain expected class');

		Config::inst()->update('FormField', 'default_classes', array(
			'class1',
			'class2',
		));

		$field = new FormField('MyField');

		$this->assertContains('class1 class2', $field->extraClass(), 'Class list does not contain expected class');

		Config::inst()->update('FormField', 'default_classes', array(
			'class3',
		));

		$field = new FormField('MyField');

		$this->assertContains('class3', $field->extraClass(), 'Class list does not contain expected class');

		$field->removeExtraClass('class3');

		$this->assertNotContains('class3', $field->extraClass(), 'Class list contains unexpected class');

		Config::inst()->update('TextField', 'default_classes', array(
			'textfield-class',
		));

		$field = new TextField('MyField');

		//check default classes inherit
		$this->assertContains('class3', $field->extraClass(), 'Class list does not contain inherited class');
		$this->assertContains('textfield-class', $field->extraClass(), 'Class list does not contain expected class');

		Config::unnest();
	}

	public function testAddExtraClass() {
		$field = new FormField('MyField');
		$field->addExtraClass('class1');
		$field->addExtraClass('class2');
		$this->assertStringEndsWith('class1 class2', $field->extraClass());
	}

	public function testRemoveExtraClass() {
		$field = new FormField('MyField');
		$field->addExtraClass('class1');
		$field->addExtraClass('class2');
		$this->assertStringEndsWith('class1 class2', $field->extraClass());
		$field->removeExtraClass('class1');
		$this->assertStringEndsWith('class2', $field->extraClass());
	}

	public function testAddManyExtraClasses() {
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

	public function testRemoveManyExtraClasses() {
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

	public function testAttributes() {
		$field = new FormField('MyField');
		$field->setAttribute('foo', 'bar');
		$this->assertEquals('bar', $field->getAttribute('foo'));
		$attrs = $field->getAttributes();
		$this->assertArrayHasKey('foo', $attrs);
		$this->assertEquals('bar', $attrs['foo']);
	}

	public function testAttributesHTML() {
		$field = new FormField('MyField');

		$field->setAttribute('foo', 'bar');
		$this->assertContains('foo="bar"', $field->getAttributesHTML());

		$field->setAttribute('foo', null);
		$this->assertNotContains('foo=', $field->getAttributesHTML());

		$field->setAttribute('foo', '');
		$this->assertNotContains('foo=', $field->getAttributesHTML());

		$field->setAttribute('foo', false);
		$this->assertNotContains('foo=', $field->getAttributesHTML());

		$field->setAttribute('foo', true);
		$this->assertContains('foo="foo"', $field->getAttributesHTML());

		$field->setAttribute('foo', 'false');
		$this->assertContains('foo="false"', $field->getAttributesHTML());

		$field->setAttribute('foo', 'true');
		$this->assertContains('foo="true"', $field->getAttributesHTML());

		$field->setAttribute('foo', 0);
		$this->assertContains('foo="0"', $field->getAttributesHTML());

		$field->setAttribute('one', 1);
		$field->setAttribute('two', 2);
		$field->setAttribute('three', 3);
		$this->assertNotContains('one="1"', $field->getAttributesHTML('one', 'two'));
		$this->assertNotContains('two="2"', $field->getAttributesHTML('one', 'two'));
		$this->assertContains('three="3"', $field->getAttributesHTML('one', 'two'));
	}

	public function testReadonly() {
		$field = new FormField('MyField');
		$field->setReadonly(true);
		$this->assertContains('readonly="readonly"', $field->getAttributesHTML());
		$field->setReadonly(false);
		$this->assertNotContains('readonly="readonly"', $field->getAttributesHTML());
	}

	public function testDisabled() {
		$field = new FormField('MyField');
		$field->setDisabled(true);
		$this->assertContains('disabled="disabled"', $field->getAttributesHTML());
		$field->setDisabled(false);
		$this->assertNotContains('disabled="disabled"', $field->getAttributesHTML());
	}

	public function testEveryFieldTransformsReadonlyAsClone() {
		$fieldClasses = ClassInfo::subclassesFor('FormField');
		foreach($fieldClasses as $fieldClass) {
			$reflectionClass = new ReflectionClass($fieldClass);
			if(!$reflectionClass->isInstantiable()) continue;
			$constructor = $reflectionClass->getMethod('__construct');
			if($constructor->getNumberOfRequiredParameters() > 1) continue;
			if($fieldClass == 'CompositeField' || is_subclass_of($fieldClass, 'CompositeField')) continue;

			if ( $fieldClass = 'NullableField' ) {
				$instance = new $fieldClass(new TextField("{$fieldClass}_instance"));
			} else {
				$instance = new $fieldClass("{$fieldClass}_instance");
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

	public function testEveryFieldTransformsDisabledAsClone() {
		$fieldClasses = ClassInfo::subclassesFor('FormField');
		foreach($fieldClasses as $fieldClass) {
			$reflectionClass = new ReflectionClass($fieldClass);
			if(!$reflectionClass->isInstantiable()) continue;
			$constructor = $reflectionClass->getMethod('__construct');
			if($constructor->getNumberOfRequiredParameters() > 1) continue;
			if($fieldClass == 'CompositeField' || is_subclass_of($fieldClass, 'CompositeField')) continue;

			if ( $fieldClass = 'NullableField' ) {
				$instance = new $fieldClass(new TextField("{$fieldClass}_instance"));
			} else {
				$instance = new $fieldClass("{$fieldClass}_instance");
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

	public function testUpdateAttributes() {
		$field = new FormField('MyField');
		$this->assertArrayHasKey('extended', $field->getAttributes());
	}

	public function testSetSchemaComponent() {
		$field = new FormField('MyField');
		$field = $field->setSchemaComponent('MyComponent');
		$component = $field->getSchemaComponent();
		$this->assertEquals('MyComponent', $component);
	}

	public function testGetSchemaDataDefaults() {
		$field = new FormField('MyField');
		$schema = $field->getSchemaDataDefaults();
		$this->assertInternalType('array', $schema);
	}

	public function testGetSchemaData() {
		$field = new FormField('MyField');
		$schema = $field->getSchemaData();
		$this->assertEquals('MyField', $schema['name']);

		// Make sure the schema data is up-to-date with object properties.
		$field->setName('UpdatedField');
		$schema = $field->getSchemaData();
		$this->assertEquals($field->getName(), $schema['name']);
	}

	public function testSetSchemaData() {
		$field = new FormField('MyField');

		// Make sure the user can update values.
		$field = $field->setSchemaData(['name' => 'MyUpdatedField']);
		$schema = $field->getSchemaData();
		$this->assertEquals($schema['name'], 'MyUpdatedField');

		// Make user the user can't define custom keys on the schema.
		$field = $field->setSchemaData(['myCustomKey' => 'yolo']);
		$schema = $field->getSchemaData();
		$this->assertEquals(array_key_exists('myCustomKey', $schema), false);
	}

	public function testGetSchemaState() {
		$field = new FormField('MyField');
		$field->setValue('My value');
		$schema = $field->getSchemaState();
		$this->assertEquals('My value', $schema['value']);
	}

	public function testSetSchemaState() {
		$field = new FormField('MyField');

		// Make sure the user can update values.
		$field = $field->setSchemaState(['value' => 'My custom value']);
		$schema = $field->getSchemaState();
		$this->assertEquals($schema['value'], 'My custom value');

		// Make user the user can't define custom keys on the schema.
		$field = $field->setSchemaState(['myCustomKey' => 'yolo']);
		$schema = $field->getSchemaState();
		$this->assertEquals(array_key_exists('myCustomKey', $schema), false);
	}

	public function testGetSchemaStateWithFormValidation() {
		$field = new FormField('MyField');
		$validator = new RequiredFields('MyField');
		$form = new Form(new Controller(), 'TestForm', new FieldList($field), new FieldList(), $validator);
		$validator->validationError('MyField', 'Something is wrong', 'error');
		$schema = $field->getSchemaState();
		$this->assertEquals(count($schema['messages']), 1);
		$this->assertEquals('Something is wrong', $schema['messages'][0]['value']);
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class FormFieldTest_Extension extends Extension implements TestOnly {

	public function updateAttributes(&$attrs) {
		$attrs['extended'] = true;
	}

}
