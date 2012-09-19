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
		$this->origTimeFormat = TimeField::$default_config['timeformat'];
		TimeField::$default_config['timeformat'] = 'HH:mm:ss';
	}
	
	public function tearDown() {
		parent::tearDown();
		
		i18n::set_locale($this->originalLocale);
		TimeField::$default_config['timeformat'] = $this->origTimeFormat;
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
	}
		
	public function testOverrideWithNull() {
		$field = new TimeField('Time', 'Time');
		
		$field->setValue('11:00pm');
		$field->setValue('');
		$this->assertEquals($field->dataValue(), '');
	}
}
