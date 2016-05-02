<?php
/**
 * @package framework
 * @subpackage tests
 */
class DateFieldTest extends SapphireTest {

	public function setUp() {
		parent::setUp();

		$this->originalLocale = i18n::get_locale();
		i18n::set_locale('en_NZ');
		$this->origConfig = Config::inst()->get('DateField', 'default_config');
		Config::inst()->update('DateField', 'default_config', array('dateformat' => 'dd/MM/yyyy'));
	}

	public function tearDown() {
		parent::tearDown();

		i18n::set_locale($this->originalLocale);
		Config::inst()->remove('DateField', 'default_config');
		Config::inst()->update('DateField', 'default_config', $this->origConfig);
	}

	public function testValidateMinDate() {
		$f = new DateField('Date');
		$f->setConfig('min', '2009-03-31');
		$f->setValue('2010-03-31');
		$this->assertTrue($f->validate(new RequiredFields()), 'Date above min date');

		$f = new DateField('Date');
		$f->setConfig('min', '2009-03-31');
		$f->setValue('1999-03-31');
		$this->assertFalse($f->validate(new RequiredFields()), 'Date below min date');

		$f = new DateField('Date');
		$f->setConfig('min', '2009-03-31');
		$f->setValue('2009-03-31');
		$this->assertTrue($f->validate(new RequiredFields()), 'Date matching min date');
	}

	public function testValidateMinDateStrtotime() {
		$f = new DateField('Date');
		$f->setConfig('min', '-7 days');
		$f->setValue(strftime('%Y-%m-%d', strtotime('-8 days')));
		$this->assertFalse($f->validate(new RequiredFields()), 'Date below min date, with strtotime');

		$f = new DateField('Date');
		$f->setConfig('min', '-7 days');
		$f->setValue(strftime('%Y-%m-%d', strtotime('-7 days')));
		$this->assertTrue($f->validate(new RequiredFields()), 'Date matching min date, with strtotime');
	}

	public function testValidateMaxDateStrtotime() {
		$f = new DateField('Date');
		$f->setConfig('max', '7 days');
		$f->setValue(strftime('%Y-%m-%d', strtotime('8 days')));
		$this->assertFalse($f->validate(new RequiredFields()), 'Date above max date, with strtotime');

		$f = new DateField('Date');
		$f->setConfig('max', '7 days');
		$f->setValue(strftime('%Y-%m-%d', strtotime('7 days')));
		$this->assertTrue($f->validate(new RequiredFields()), 'Date matching max date, with strtotime');
	}

	public function testValidateMaxDate() {
		$f = new DateField('Date');
		$f->setConfig('max', '2009-03-31');
		$f->setValue('1999-03-31');
		$this->assertTrue($f->validate(new RequiredFields()), 'Date above min date');

		$f = new DateField('Date');
		$f->setConfig('max', '2009-03-31');
		$f->setValue('2010-03-31');
		$this->assertFalse($f->validate(new RequiredFields()), 'Date above max date');

		$f = new DateField('Date');
		$f->setConfig('max', '2009-03-31');
		$f->setValue('2009-03-31');
		$this->assertTrue($f->validate(new RequiredFields()), 'Date matching max date');
	}

	public function testConstructorWithoutArgs() {
		$f = new DateField('Date');
		$this->assertEquals($f->dataValue(), null);
	}

	public function testConstructorWithDateString() {
		$f = new DateField('Date', 'Date', '29/03/2003');
		$this->assertEquals($f->dataValue(), '2003-03-29');
	}

	public function testSetValueWithDateString() {
		$f = new DateField('Date', 'Date');
		$f->setValue('29/03/2003');
		$this->assertEquals($f->dataValue(), '2003-03-29');
	}

	public function testSetValueWithDateArray() {
		$f = new DateField('Date', 'Date');
		$f->setConfig('dmyfields', true);
		$f->setValue(array('day' => 29, 'month' => 03, 'year' => 2003));
		$this->assertEquals($f->dataValue(), '2003-03-29');
	}

	public function testConstructorWithIsoDate() {
		// used by Form->loadDataFrom()
		$f = new DateField('Date', 'Date', '2003-03-29');
		$this->assertEquals($f->dataValue(), '2003-03-29');
	}

	public function testValidateDMY() {
		$f = new DateField('Date', 'Date', '29/03/2003');
		$this->assertTrue($f->validate(new RequiredFields()));

		$f = new DateField('Date', 'Date', 'wrong');
		$this->assertFalse($f->validate(new RequiredFields()));
	}

	public function testValidateArray() {
		$f = new DateField('Date', 'Date');
		$f->setConfig('dmyfields', true);
		$f->setValue(array('day' => 29, 'month' => 03, 'year' => 2003));
		$this->assertTrue($f->validate(new RequiredFields()));

		$f->setValue(null);
		$this->assertTrue($f->validate(new RequiredFields()), 'NULL values are validating TRUE');

		$f->setValue(array());
		$this->assertTrue($f->validate(new RequiredFields()), 'Empty array values are validating TRUE');

		$f->setValue(array('day' => null, 'month' => null, 'year' => null));
		$this->assertTrue($f->validate(new RequiredFields()), 'Empty array values with keys are validating TRUE');

		// TODO Fix array validation
		// $f = new DateField('Date', 'Date', array('day' => 9999, 'month' => 9999, 'year' => 9999));
		// $this->assertFalse($f->validate(new RequiredFields()));
	}

	public function testValidateEmptyArrayValuesSetsNullForValueObject() {
		$f = new DateField('Date', 'Date');
		$f->setConfig('dmyfields', true);

		$f->setValue(array('day' => '', 'month' => '', 'year' => ''));
		$this->assertNull($f->dataValue());

		$f->setValue(array('day' => null, 'month' => null, 'year' => null));
		$this->assertNull($f->dataValue());
	}

	public function testValidateArrayValue() {
		$f = new DateField('Date', 'Date');
		$this->assertTrue($f->validateArrayValue(array('day' => 29, 'month' => 03, 'year' => 2003)));
		$this->assertFalse($f->validateArrayValue(array('month' => 03, 'year' => 2003)));
		$this->assertFalse($f->validateArrayValue(array('day' => 99, 'month' => 99, 'year' => 2003)));
	}

	public function testFormatEnNz() {
		/* We get YYYY-MM-DD format as the data value for DD/MM/YYYY input value */
		$f = new DateField('Date', 'Date', '29/03/2003');
		$this->assertEquals($f->dataValue(), '2003-03-29');
	}

	public function testSetLocale() {
		// should get en_NZ by default through setUp()
		$f = new DateField('Date', 'Date', '29/03/2003');
		$f->setLocale('de_DE');
		$f->setValue('29.06.2006');
		$this->assertEquals($f->dataValue(), '2006-06-29');
	}

	/**
	 * Note: This is mostly tested for legacy reasons
	 */
	public function testMDYFormat() {
		$dateField = new DateField('Date', 'Date');
		$dateField->setConfig('dateformat', 'd/M/Y');
		$dateField->setValue('31/03/2003');
		$this->assertEquals(
			$dateField->dataValue(),
			'2003-03-31',
			"We get MM-DD-YYYY format as the data value for YYYY-MM-DD input value"
		);

		$dateField2 = new DateField('Date', 'Date');
		$dateField2->setConfig('dateformat', 'd/M/Y');
		$dateField2->setValue('04/3/03');
		$this->assertEquals(
			$dateField2->dataValue(),
			'2003-03-04',
			"Even if input value hasn't got leading 0's in it we still get the correct data value"
		);
	}

}
