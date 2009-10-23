<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class FormFieldTest extends SapphireTest {
	
	function testFieldHasExtraClass() {		
		/* TextField has an extra class name and is in the HTML the field returns */
		$textField = new TextField('Name');
		$textField->addExtraClass('thisIsMyClassNameForTheFormField');
		preg_match('/thisIsMyClassNameForTheFormField/', $textField->Field(), $matches);
		$this->assertTrue($matches[0] == 'thisIsMyClassNameForTheFormField');
		
		/* EmailField has an extra class name and is in the HTML the field returns */
		$emailField = new EmailField('Email');
		$emailField->addExtraClass('thisIsMyExtraClassForEmailField');
		preg_match('/thisIsMyExtraClassForEmailField/', $emailField->Field(), $matches);
		$this->assertTrue($matches[0] == 'thisIsMyExtraClassForEmailField');
		
		/* OptionsetField has an extra class name and is in the HTML the field returns */
		$optionsetField = new OptionsetField('FeelingOk', 'Are you feeling ok?', array(0 => 'No', 1 => 'Yes'), '', null, '(Select one)');
		$optionsetField->addExtraClass('thisIsMyExtraClassForOptionsetField');
		preg_match('/thisIsMyExtraClassForOptionsetField/', $optionsetField->Field(), $matches);
		$this->assertTrue($matches[0] == 'thisIsMyExtraClassForOptionsetField');
	}
	
	function testEveryFieldTransformsReadonlyAsClone() {
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
				"FormField class '{$fieldClass} retains its readonly state after calling performReadonlyTransformation()"
			);
			$this->assertTrue(
				$readonlyInstance->isReadonly(),
				"FormField class '{$fieldClass} returns a valid readonly representation as of isReadonly()"
			);
			$this->assertNotSame(
				$readonlyInstance,
				$instance,
				"FormField class '{$fieldClass} returns a valid cloned readonly representation"
			);
		}
	}
	
	function testEveryFieldTransformsDisabledAsClone() {
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
				"FormField class '{$fieldClass} retains its disabled state after calling performDisabledTransformation()"
			);
			$this->assertTrue(
				$disabledInstance->isDisabled(),
				"FormField class '{$fieldClass} returns a valid disabled representation as of isDisabled()"
			);
			$this->assertNotSame(
				$disabledInstance,
				$instance,
				"FormField class '{$fieldClass} returns a valid cloned disabled representation"
			);
		}
	}
	
}
?>