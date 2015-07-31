<?php
/**
 * @package framework
 * @subpackage tests
 */
class TimeFieldTest extends SapphireTest {

	public function setUp() {
		parent::setUp();

		$this->originalLocale = i18n::get_locale();
		i18n::set_locale('en_NZ');
		$this->origTimeConfig = Config::inst()->get('TimeField', 'default_config');
		Config::inst()->update('TimeField', 'default_config', array('timeformat' => 'HH:mm:ss'));
	}

	public function tearDown() {
		parent::tearDown();

		i18n::set_locale($this->originalLocale);
		Config::inst()->remove('TimeField', 'default_config');
		Config::inst()->update('TimeField', 'default_config', $this->origTimeConfig);
	}

	public function testConstructorWithoutArgs() {
		$f = new TimeField('Time');
		$this->assertEquals($f->dataValue(), null);
	}

	public function testConstructorWithString() {
		$f = new TimeField('Time', 'Time', '23:00:00');
		$this->assertEquals($f->dataValue(), '23:00:00');
	}

	public function testValidate() {
		$f = new TimeField('Time', 'Time', '11pm');
		$this->assertTrue($f->validate(new RequiredFields()));

		$f = new TimeField('Time', 'Time', '23:59');
		$this->assertTrue($f->validate(new RequiredFields()));

		$f = new TimeField('Time', 'Time', 'wrong');
		$this->assertFalse($f->validate(new RequiredFields()));
	}

	public function testSetLocale() {
		// should get en_NZ by default through setUp()
		$f = new TimeField('Time', 'Time');
		$f->setLocale('de_DE');
		// TODO Find a hour format thats actually different
		$f->setValue('23:59');
		$this->assertEquals($f->dataValue(), '23:59:00');
	}

	public function testSetValueWithUseStrToTime() {
		$f = new TimeField('Time', 'Time');
		$f->setValue('11pm');
		$this->assertEquals($f->dataValue(), '23:00:00',
			'Setting value to "11pm" parses with use_strtotime enabled'
		);
		$this->assertTrue($f->validate(new RequiredFields()));

		$f = new TimeField('Time', 'Time');
		$f->setConfig('use_strtotime', false);
		$f->setValue('11pm');
		$this->assertEquals($f->dataValue(), null,
			'Setting value to "11pm" parses with use_strtotime enabled'
		);
		$this->assertFalse($f->validate(new RequiredFields()));

		$f = new TimeField('Time', 'Time');
		$f->setValue('11pm');
		$this->assertEquals($f->dataValue(), '23:00:00');

		$f = new TimeField('Time', 'Time');
		$f->setValue('11:59pm');
		$this->assertEquals($f->dataValue(), '23:59:00');

		$f = new TimeField('Time', 'Time');
		$f->setValue('11:59 pm');
		$this->assertEquals($f->dataValue(), '23:59:00');

		$f = new TimeField('Time', 'Time');
		$f->setValue('11:59:38 pm');
		$this->assertEquals($f->dataValue(), '23:59:38');

		$f = new TimeField('Time', 'Time');
		$f->setValue('23:59');
		$this->assertEquals($f->dataValue(), '23:59:00');

		$f = new TimeField('Time', 'Time');
		$f->setValue('23:59:38');
		$this->assertEquals($f->dataValue(), '23:59:38');

		$f = new TimeField('Time', 'Time');
		$f->setValue('12:00 am');
		$this->assertEquals($f->dataValue(), '00:00:00');

		$f = new TimeField('Time', 'Time');
		$f->setValue('12:00:01 am');
		$this->assertEquals($f->dataValue(), '00:00:01');
	}

	public function testOverrideWithNull() {
		$field = new TimeField('Time', 'Time');

		$field->setValue('11:00pm');
		$field->setValue('');
		$this->assertEquals($field->dataValue(), '');
	}

	/**
	 * Test that AM/PM is preserved correctly in various situations
	 */
	public function testPreserveAMPM() {

		// Test with timeformat that includes hour

		// Check pm
		$f = new TimeField('Time', 'Time');
		$f->setConfig('timeformat', 'h:mm:ss a');
		$f->setValue('3:59 pm');
		$this->assertEquals($f->dataValue(), '15:59:00');

		// Check am
		$f = new TimeField('Time', 'Time');
		$f->setConfig('timeformat', 'h:mm:ss a');
		$f->setValue('3:59 am');
		$this->assertEquals($f->dataValue(), '03:59:00');

		// Check with ISO date/time
		$f = new TimeField('Time', 'Time');
		$f->setConfig('timeformat', 'h:mm:ss a');
		$f->setValue('15:59:00');
		$this->assertEquals($f->dataValue(), '15:59:00');

		// ISO am
		$f = new TimeField('Time', 'Time');
		$f->setConfig('timeformat', 'h:mm:ss a');
		$f->setValue('03:59:00');
		$this->assertEquals($f->dataValue(), '03:59:00');
	}
}
