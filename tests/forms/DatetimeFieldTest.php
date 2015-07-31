<?php
/**
 * @package framework
 * @subpackage tests
 */
class DatetimeFieldTest extends SapphireTest {

	public function setUp() {
		parent::setUp();

		$this->originalLocale = i18n::get_locale();
		i18n::set_locale('en_NZ');
		$this->origDateConfig = Config::inst()->get('DateField', 'default_config');
		$this->origTimeConfig = Config::inst()->get('TimeField', 'default_config');
		Config::inst()->update('DateField', 'default_config', array('dateformat' => 'dd/MM/yyyy'));
		Config::inst()->update('TimeField', 'default_config', array('timeformat' => 'HH:mm:ss'));
	}

	public function tearDown() {
		parent::tearDown();

		i18n::set_locale($this->originalLocale);
		Config::inst()->remove('DateField', 'default_config');
		Config::inst()->update('DateField', 'default_config', $this->origDateConfig);
		Config::inst()->remove('TimeField', 'default_config');
		Config::inst()->update('TimeField', 'default_config', $this->origTimeConfig);
	}

	public function testFormSaveInto() {
		$f = new DatetimeField('MyDatetime', null);
		$form = $this->getMockForm();
		$form->Fields()->push($f);
		$f->setValue(array(
			'date' => '29/03/2003',
			'time' => '23:59:38'
		));
		$m = new DatetimeFieldTest_Model();
		$form->saveInto($m);
		$this->assertEquals('2003-03-29 23:59:38', $m->MyDatetime);
	}

	public function testDataValue() {
		$f = new DatetimeField('Datetime');
		$this->assertEquals(null, $f->dataValue(), 'Empty field');

		$f = new DatetimeField('Datetime', null, '2003-03-29 23:59:38');
		$this->assertEquals('2003-03-29 23:59:38', $f->dataValue(), 'From date/time string');
	}

	public function testConstructorWithoutArgs() {
		$f = new DatetimeField('Datetime');
		$this->assertEquals($f->dataValue(), null);
	}

	// /**
	//  * @expectedException InvalidArgumentException
	//  */
	// public function testConstructorWithLocalizedDateString() {
	// 	$f = new DatetimeField('Datetime', 'Datetime', '29/03/2003 23:59:38');
	// }

	public function testConstructorWithIsoDate() {
		// used by Form->loadDataFrom()
		$f = new DatetimeField('Datetime', 'Datetime', '2003-03-29 23:59:38');
		$this->assertEquals($f->dataValue(), '2003-03-29 23:59:38');
	}

	// /**
	//  * @expectedException InvalidArgumentException
	//  */
	// public function testSetValueWithDateString() {
	// 	$f = new DatetimeField('Datetime', 'Datetime');
	// 	$f->setValue('29/03/2003');
	// }

	public function testSetValueWithDateTimeString() {
		$f = new DatetimeField('Datetime', 'Datetime');
		$f->setValue('2003-03-29 23:59:38');
		$this->assertEquals($f->dataValue(), '2003-03-29 23:59:38');
	}

	public function testSetValueWithArray() {
		$f = new DatetimeField('Datetime', 'Datetime');
		// Values can only be localized (= non-ISO) in array notation
		$f->setValue(array(
			'date' => '29/03/2003',
			'time' => '11pm'
		));
		$this->assertEquals($f->dataValue(), '2003-03-29 23:00:00');
	}

	public function testSetValueWithDmyArray() {
		$f = new DatetimeField('Datetime', 'Datetime');
		$f->getDateField()->setConfig('dmyfields', true);
		$f->setValue(array(
			'date' => array('day' => 29, 'month' => 03, 'year' => 2003),
			'time' => '11pm'
		));
		$this->assertEquals($f->dataValue(), '2003-03-29 23:00:00');
	}

	public function testValidate() {
		$f = new DatetimeField('Datetime', 'Datetime', '2003-03-29 23:59:38');
		$this->assertTrue($f->validate(new RequiredFields()));

		$f = new DatetimeField('Datetime', 'Datetime', '2003-03-29');
		$this->assertTrue($f->validate(new RequiredFields()));

		$f = new DatetimeField('Datetime', 'Datetime', 'wrong');
		$this->assertFalse($f->validate(new RequiredFields()));
	}

	public function testTimezone() {
		$oldTz = date_default_timezone_get();

		date_default_timezone_set('Europe/Berlin');
		// Berlin and Auckland have 12h time difference in northern hemisphere winter
		$f = new DatetimeField('Datetime', 'Datetime', '2003-12-24 23:59:59');
		$f->setConfig('usertimezone', 'Pacific/Auckland');
		$this->assertEquals('25/12/2003 11:59:59', $f->Value(),
			'User value is formatted, and in user timezone');
		$this->assertEquals('25/12/2003', $f->getDateField()->Value());
		$this->assertEquals('11:59:59', $f->getTimeField()->Value());
		$this->assertEquals('2003-12-24 23:59:59', $f->dataValue(),
			'Data value is unformatted, and in server timezone');

		date_default_timezone_set($oldTz);
	}

	public function testTimezoneFromFormSubmission() {
		$oldTz = date_default_timezone_get();

		date_default_timezone_set('Europe/Berlin');
		// Berlin and Auckland have 12h time difference in northern hemisphere summer, but Berlin and Moscow only 2h.
		$f = new DatetimeField('Datetime', 'Datetime');
		$f->setConfig('usertimezone', 'Pacific/Auckland'); // should be overridden by form submission
		$f->setValue(array(
			// pass in default format, at user time (Moscow)
			'date' => '24/06/2003',
			'time' => '23:59:59',
			'timezone' => 'Europe/Moscow'
		));
		$this->assertEquals('24/06/2003 23:59:59', $f->Value(), 'View composite value matches user timezone');
		$this->assertEquals('24/06/2003', $f->getDateField()->Value(), 'View date part matches user timezone');
		$this->assertEquals('23:59:59', $f->getTimeField()->Value(), 'View time part matches user timezone');
		// 2h difference to Moscow
		$this->assertEquals('2003-06-24 21:59:59', $f->dataValue(), 'Data value matches server timezone');

		date_default_timezone_set($oldTz);
	}

	public function testTimezoneFromConfig() {
		$oldTz = date_default_timezone_get();

		date_default_timezone_set('Europe/Berlin');
		// Berlin and Auckland have 12h time difference in northern hemisphere summer, but Berlin and Moscow only 2h.
		$f = new DatetimeField('Datetime', 'Datetime');
		$f->setConfig('usertimezone', 'Europe/Moscow');
		$f->setValue(array(
			// pass in default format, at user time (Moscow)
			'date' => '24/06/2003',
			'time' => '23:59:59',
		));
		$this->assertEquals('2003-06-24 21:59:59', $f->dataValue(), 'Data value matches server timezone');

		date_default_timezone_set($oldTz);
	}

	public function testSetDateField() {
		$form = $this->getMockForm();
		$field = new DatetimeField('Datetime', 'Datetime');
		$field->setForm($form);
		$field->setValue(array(
			'date' => '24/06/2003',
			'time' => '23:59:59',
		));
		$dateField = new DateField('Datetime[date]');
		$field->setDateField($dateField);

		$this->assertEquals(
			$dateField->getForm(),
			$form,
			'Sets form on new field'
		);

		$this->assertEquals(
			'2003-06-24',
			$dateField->dataValue(),
			'Sets existing value on new field'
		);
	}

	public function testSetTimeField() {
		$form = $this->getMockForm();
		$field = new DatetimeField('Datetime', 'Datetime');
		$field->setForm($form);
		$field->setValue(array(
			'date' => '24/06/2003',
			'time' => '23:59:59',
		));
		$timeField = new TimeField('Datetime[time]');
		$field->setTimeField($timeField);

		$this->assertEquals(
			$timeField->getForm(),
			$form,
			'Sets form on new field'
		);

		$this->assertEquals(
			'23:59:59',
			$timeField->dataValue(),
			'Sets existing value on new field'
		);
	}

	public function testGetName() {
		$field = new DatetimeField('Datetime');
		
		$this->assertEquals('Datetime', $field->getName());
		$this->assertEquals('Datetime[date]', $field->getDateField()->getName());
		$this->assertEquals('Datetime[time]', $field->getTimeField()->getName());
		$this->assertEquals('Datetime[timezone]', $field->getTimezoneField()->getName());
	}

	public function testSetName() {
		$field = new DatetimeField('Datetime', 'Datetime');
		$field->setName('CustomDatetime');
		$this->assertEquals('CustomDatetime', $field->getName());
		$this->assertEquals('CustomDatetime[date]', $field->getDateField()->getName());
		$this->assertEquals('CustomDatetime[time]', $field->getTimeField()->getName());
		$this->assertEquals('CustomDatetime[timezone]', $field->getTimezoneField()->getName());
	}

	protected function getMockForm() {
		return new Form(
			new Controller(),
			'Form',
			new FieldList(),
			new FieldList(
				new FormAction('doSubmit')
			)
		);
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class DatetimeFieldTest_Model extends DataObject implements TestOnly {

	private static $db = array(
		'MyDatetime' => 'SS_Datetime'
	);

}
