<?php
/**
 * @package sapphire 
 * @subpackage forms
 */
class MemberDatetimeOptionsetFieldTest extends SapphireTest {

	public static $fixture_file = 'sapphire/tests/forms/MemberDatetimeOptionsetFieldTest.yml';

	protected function createDateFormatFieldForMember($member) {
		require_once 'Zend/Date.php';
		$defaultDateFormat = Zend_Locale_Format::getDateFormat($member->Locale);
		$dateFormatMap = array(
			'MMM d, yyyy' => Zend_Date::now()->toString('MMM d, yyyy'),
			'yyyy/MM/dd' => Zend_Date::now()->toString('yyyy/MM/dd'),
			'MM/dd/yyyy' => Zend_Date::now()->toString('MM/dd/yyyy'),
			'dd/MM/yyyy' => Zend_Date::now()->toString('dd/MM/yyyy'),
		);
		$dateFormatMap[$defaultDateFormat] = Zend_Date::now()->toString($defaultDateFormat) . ' (default)';
		$field = new Member_DatetimeOptionsetField(
			'DateFormat',
			'Date format',
			$dateFormatMap
		);
		$field->setValue($member->DateFormat);
		return $field;
	}

	protected function createTimeFormatFieldForMember($member) {
		require_once 'Zend/Date.php';
		$defaultTimeFormat = Zend_Locale_Format::getTimeFormat($member->Locale);
		$timeFormatMap = array(
			'h:mm a' => Zend_Date::now()->toString('h:mm a'),
			'H:mm' => Zend_Date::now()->toString('H:mm'),
		);
		$timeFormatMap[$defaultTimeFormat] = Zend_Date::now()->toString($defaultTimeFormat) . ' (default)';
		$field = new Member_DatetimeOptionsetField(
			'TimeFormat',
			'Time format',
			$timeFormatMap
		);
		$field->setValue($member->TimeFormat);
		return $field;
	}

	function testDateFormatDefaultCheckedInFormField() {
		$field = $this->createDateFormatFieldForMember($this->objFromFixture('Member', 'noformatmember'));
		$field->setForm(new Form(new MemberDatetimeOptionsetFieldTest_Controller(), 'Form', new FieldSet(), new FieldSet())); // fake form
		$parser = new CSSContentParser($field->Field());
		$xmlArr = $parser->getBySelector('#Form_Form_DateFormat_MM_dd_yyyy');
		$this->assertEquals('checked', (string) $xmlArr[0]['checked']);
	}

	function testTimeFormatDefaultCheckedInFormField() {
		$field = $this->createTimeFormatFieldForMember($this->objFromFixture('Member', 'noformatmember'));
		$field->setForm(new Form(new MemberDatetimeOptionsetFieldTest_Controller(), 'Form', new FieldSet(), new FieldSet())); // fake form
		$parser = new CSSContentParser($field->Field());
		$xmlArr = $parser->getBySelector('#Form_Form_TimeFormat_hh_mm_a');
		$this->assertEquals('checked', (string) $xmlArr[0]['checked']);
	}

	function testDateFormatChosenIsCheckedInFormField() {
		$member = $this->objFromFixture('Member', 'noformatmember');
		$member->setField('DateFormat', 'MM/dd/yyyy');
		$field = $this->createDateFormatFieldForMember($member);
		$field->setForm(new Form(new MemberDatetimeOptionsetFieldTest_Controller(), 'Form', new FieldSet(), new FieldSet())); // fake form
		$parser = new CSSContentParser($field->Field());
		$xmlArr = $parser->getBySelector('#Form_Form_DateFormat_MM_dd_yyyy');
		$this->assertEquals('checked', (string) $xmlArr[0]['checked']);
	}

	function testDateFormatCustomFormatAppearsInCustomInputInField() {
		$member = $this->objFromFixture('Member', 'noformatmember');
		$member->setField('DateFormat', 'dd MM yy');
		$field = $this->createDateFormatFieldForMember($member);
		$field->setForm(new Form(new MemberDatetimeOptionsetFieldTest_Controller(), 'Form', new FieldSet(), new FieldSet())); // fake form
		$parser = new CSSContentParser($field->Field());
		$xmlInputArr = $parser->getBySelector('.valCustom input');
		$xmlPreview = $parser->getBySelector('.preview');
		$this->assertEquals('checked', (string) $xmlInputArr[0]['checked']);
		$this->assertEquals('dd MM yy', (string) $xmlInputArr[1]['value']);
	}

	function testDateFormValid() {
		$field = new Member_DatetimeOptionsetField('DateFormat', 'DateFormat');
		$this->assertTrue($field->validate());
		$_POST['DateFormat_custom'] = 'dd MM yyyy';
		$this->assertTrue($field->validate());
		$_POST['DateFormat_custom'] = 'sdfdsfdfd1244';
		$this->assertFalse($field->validate());
	}

}
class MemberDatetimeOptionsetFieldTest_Controller extends Controller {

	function Link() {
		return 'test';
	}

}