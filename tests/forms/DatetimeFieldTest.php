<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class DatetimeFieldTest extends SapphireTest {
	
	function setUp() {
		parent::setUp();
		
		$this->originalLocale = i18n::get_locale();
		i18n::set_locale('en_NZ');
	}
	
	function tearDown() {
		parent::tearDown();
		
		i18n::set_locale($this->originalLocale);
	}

	function testFormSaveInto() {
		$form = new Form(
			new Controller(), 
			'Form',
			new FieldSet(
				$f = new DatetimeField('MyDatetime', null)
			),
			new FieldSet(
				new FormAction('doSubmit')
			)
		);
		$f->setValue(array(
			'date' => '29/03/2003',
			'time' => '23:59:38'
		));
		$m = new DatetimeFieldTest_Model();
		$form->saveInto($m);
		$this->assertEquals('2003-03-29 23:59:38', $m->MyDatetime);
	}
	
	function testDataValue() {
		$f = new DatetimeField('Datetime');
		$this->assertEquals(null, $f->dataValue(), 'Empty field');
		
		$f = new DatetimeField('Datetime', null, '2003-03-29 23:59:38');
		$this->assertEquals('2003-03-29 23:59:38', $f->dataValue(), 'From date/time string');
		
		$f = new DatetimeField('Datetime', null, '2003-03-29');
		$this->assertEquals('2003-03-29 00:00:00', $f->dataValue(), 'From date string (no time)');
		
		$f = new DatetimeField('Datetime', null, array('date' => '2003-03-29', 'time' => null));
		$this->assertEquals('2003-03-29 00:00:00', $f->dataValue(), 'From date array (no time)');
	}
	
	function testConstructorWithoutArgs() {
		$f = new DatetimeField('Datetime');
		$this->assertEquals($f->dataValue(), null);
	}
	
	// /**
	//  * @expectedException InvalidArgumentException
	//  */
	// function testConstructorWithLocalizedDateString() {
	// 	$f = new DatetimeField('Datetime', 'Datetime', '29/03/2003 23:59:38');
	// }
	
	function testConstructorWithIsoDate() {
		// used by Form->loadDataFrom()
		$f = new DatetimeField('Datetime', 'Datetime', '2003-03-29 23:59:38');
		$this->assertEquals($f->dataValue(), '2003-03-29 23:59:38');
	}
	
	// /**
	//  * @expectedException InvalidArgumentException
	//  */
	// function testSetValueWithDateString() {
	// 	$f = new DatetimeField('Datetime', 'Datetime');
	// 	$f->setValue('29/03/2003');
	// }
	
	function testSetValueWithDateTimeString() {
		$f = new DatetimeField('Datetime', 'Datetime');
		$f->setValue('2003-03-29 23:59:38');
		$this->assertEquals($f->dataValue(), '2003-03-29 23:59:38');
	}
	
	function testSetValueWithArray() {
		$f = new DatetimeField('Datetime', 'Datetime');
		// Values can only be localized (= non-ISO) in array notation
		$f->setValue(array(
			'date' => '29/03/2003',
			'time' => '11pm'
		));
		$this->assertEquals($f->dataValue(), '2003-03-29 23:00:00');
	}
	
	function testSetValueWithDmyArray() {
		$f = new DatetimeField('Datetime', 'Datetime');
		$f->getDateField()->setConfig('dmyfields', true);
		$f->setValue(array(
			'date' => array('day' => 29, 'month' => 03, 'year' => 2003),
			'time' => '11pm'
		));
		$this->assertEquals($f->dataValue(), '2003-03-29 23:00:00');
	}
	
	function testValidate() {
		$f = new DatetimeField('Datetime', 'Datetime', '2003-03-29 23:59:38');
		$this->assertTrue($f->validate(new RequiredFields()));
		
		$f = new DatetimeField('Datetime', 'Datetime', 'wrong');
		$this->assertFalse($f->validate(new RequiredFields()));
	}
}

/**
 * @package sapphire
 * @subpackage tests
 */
class DatetimeFieldTest_Model extends DataObject implements TestOnly {
	
	static $db = array(
		'MyDatetime' => 'SS_Datetime'
	);
	
}