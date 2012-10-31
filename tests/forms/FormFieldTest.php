<?php
/**
 * @package framework
 * @subpackage tests
 */
class FormFieldTest extends SapphireTest {

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
	
}
