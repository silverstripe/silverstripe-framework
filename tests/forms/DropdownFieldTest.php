<?php
/**
 * @package framework
 * @subpackage tests
 */
class DropdownFieldTest extends SapphireTest {
	
	public function testGetSource() {
		$source = array(1=>'one');
		$field = new DropdownField('Field', null, $source);
		$this->assertEquals(
			$field->getSource(),
			array(
				1 => 'one'
			)
		);
	}
	
	public function testReadonlyField() {
		$field = new DropdownField('FeelingOk', 'Are you feeling ok?', array(0 => 'No', 1 => 'Yes'));
		$field->setEmptyString('(Select one)');
		$field->setValue(1);
		$readonlyField = $field->performReadonlyTransformation();
		preg_match('/Yes/', $field->Field(), $matches);
		$this->assertEquals($matches[0], 'Yes');
	}

	public function testEmptyStringAsLiteralConstructorArgument() {
		$source = array(1 => 'one');
		$field = new DropdownField('Field', null, $source);
		$field->setEmptyString('select...');
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => 'select...',
				1 => 'one'
			)
		);
	}

	public function testHasEmptyDefault() {
		$source = array(1 => 'one');
		$field = new DropdownField('Field', null, $source);
		$field->setHasEmptyDefault(true);
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => '',
				1 => 'one'
			)
		);
	}

	public function testEmptyDefaultStringThroughSetter() {
		$source = array(1=>'one');
		$field = new DropdownField('Field', null, $source);
		$field->setEmptyString('select...');
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => 'select...',
				1 => 'one'
			)
		);
		$this->assertTrue(
			$field->getHasEmptyDefault()
		);
	}

	public function testZeroArraySourceNotOverwrittenByEmptyString() {
		$source = array(0=>'zero');
		$field = new DropdownField('Field', null, $source);
		$field->setEmptyString('select...');
		$this->assertEquals(
			$field->getSource(),
			array(
				'' => 'select...',
				0 => 'zero'
			)
		);
	}
	
	public function testNumberOfSelectOptionsAvailable() {
		/* Create a field with a blank value */
		$field = $this->createDropdownField('(Any)');
		$this->assertEquals(count($this->findOptionElements($field->Field())), 3, '3 options are available');
		$selectedOptions = $this->findSelectedOptionElements($field->Field());
		$this->assertEquals(count($selectedOptions), 1,
			'We only have 1 selected option, since a dropdown can only possibly have one!');
		
		/* Create a field without a blank value */
		$field = $this->createDropdownField();
		$this->assertEquals(count($this->findOptionElements($field->Field())), 2, '2 options are available');
		$selectedOptions = $this->findSelectedOptionElements($field->Field());
		$this->assertEquals(count($selectedOptions), 0, 'There are no selected options');
	}
	
	public function testIntegerZeroValueSeelctedOptionBehaviour() {
		$field = $this->createDropdownField('(Any)', 0);
		$selectedOptions = $this->findSelectedOptionElements($field->Field());
		$this->assertEquals((string) $selectedOptions[0], 'No', 'The selected option is "No"');
	}

	public function testBlankStringValueSelectedOptionBehaviour() {
		$field = $this->createDropdownField('(Any)');
		$selectedOptions = $this->findSelectedOptionElements($field->Field());
		$this->assertEquals((string) $selectedOptions[0], '(Any)', 'The selected option is "(Any)"');
	}
	
	public function testNullValueSelectedOptionBehaviour() {
		$field = $this->createDropdownField('(Any)', null);
		$selectedOptions = $this->findSelectedOptionElements($field->Field());
		$this->assertEquals((string) $selectedOptions[0], '(Any)', 'The selected option is "(Any)"');
	}
	
	public function testStringValueSelectedOptionBehaviour() {
		$field = $this->createDropdownField('(Any)', '1');
		$selectedOptions = $this->findSelectedOptionElements($field->Field());
		$this->assertEquals((string) $selectedOptions[0], 'Yes', 'The selected option is "Yes"');
		$field->setSource(array(
			'Cats' => 'Cats and Kittens',
			'Dogs' => 'Dogs and Puppies'
		));
		$field->setValue('Cats');
		$selectedOptions = $this->findSelectedOptionElements($field->Field());
		$this->assertEquals((string) $selectedOptions[0], 'Cats and Kittens',
			'The selected option is "Cats and Kittens"');
	}
	
	/**
	 * Create a test dropdown field, with the option to
	 * set what source and blank value it should contain
	 * as optional parameters.
	 * 
	 * @param string|null $emptyString The text to display for the empty value
	 * @param string|integer $value The default value of the field
	 * @return DropdownField object
	 */
	public function createDropdownField($emptyString = null, $value = '') {
		/* Set up source, with 0 and 1 integers as the values */
		$source = array(
			0 => 'No',
			1 => 'Yes'
		);
		
		$field = new DropdownField('Field', null, $source, $value);
		if($emptyString !== null) $field->setEmptyString($emptyString);

		return $field;
	}

	/**
	 * Find all the <OPTION> elements from a
	 * string of HTML.
	 * 
	 * @param string $html HTML to scan for elements
	 * @return SimpleXMLElement
	 */
	public function findOptionElements($html) {
		$parser = new CSSContentParser($html);
		return $parser->getBySelector('option');
	}
	
	/**
	 * Find all the <OPTION> elements from a
	 * string of HTML that have the "selected"
	 * attribute.
	 * 
	 * @param string $html HTML to parse for elements
	 * @return array of SimpleXMLElement objects
	 */
	public function findSelectedOptionElements($html) {
		$options = $this->findOptionElements($html);
		
		/* Find any elements that have the "selected" attribute and put them into a list */
		$foundSelected = array();
		foreach($options as $option) {
			$attributes = $option->attributes();
			if($attributes) foreach($attributes as $attribute => $value) {
				if($attribute == 'selected') {
					$foundSelected[] = $option;
				}
			}
		}

		return $foundSelected;
	}
	
}
