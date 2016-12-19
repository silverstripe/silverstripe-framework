<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\MemberDatetimeOptionsetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\i18n\i18n;
use SilverStripe\Security\Member;
use Zend_Locale_Format;
use Zend_Date;

class MemberDatetimeOptionsetFieldTest extends SapphireTest
{

    protected static $fixture_file = 'MemberDatetimeOptionsetFieldTest.yml';

    protected function createDateFormatFieldForMember($member)
    {
        include_once 'Zend/Date.php';
        $defaultDateFormat = Zend_Locale_Format::getDateFormat($member->Locale);
        $dateFormatMap = array(
            'yyyy-MM-dd' => Zend_Date::now()->toString('yyyy-MM-dd'),
            'yyyy/MM/dd' => Zend_Date::now()->toString('yyyy/MM/dd'),
            'MM/dd/yyyy' => Zend_Date::now()->toString('MM/dd/yyyy'),
            'dd/MM/yyyy' => Zend_Date::now()->toString('dd/MM/yyyy'),
        );
        $dateFormatMap[$defaultDateFormat] = Zend_Date::now()->toString($defaultDateFormat) . ' (default)';
        $field = new MemberDatetimeOptionsetField(
            'DateFormat',
            'Date format',
            $dateFormatMap
        );
        $field->setValue($member->DateFormat);
        return $field;
    }

    protected function createTimeFormatFieldForMember($member)
    {
        include_once 'Zend/Date.php';
        $defaultTimeFormat = Zend_Locale_Format::getTimeFormat($member->Locale);
        $timeFormatMap = array(
            'h:mm a' => Zend_Date::now()->toString('h:mm a'),
            'H:mm' => Zend_Date::now()->toString('H:mm'),
        );
        $timeFormatMap[$defaultTimeFormat] = Zend_Date::now()->toString($defaultTimeFormat) . ' (default)';
        $field = new MemberDatetimeOptionsetField(
            'TimeFormat',
            'Time format',
            $timeFormatMap
        );
        $field->setValue($member->TimeFormat);
        return $field;
    }

    public function testDateFormatDefaultCheckedInFormField()
    {
        Config::inst()->update(i18n::class, 'date_format', 'yyyy-MM-dd');
        $field = $this->createDateFormatFieldForMember($this->objFromFixture(Member::class, 'noformatmember'));
        /**
 * @skipUpgrade
*/
        $field->setForm(
            new Form(
                new Controller(),
                'Form',
                new FieldList(),
                new FieldList()
            )
        ); // fake form
        $parser = new CSSContentParser($field->Field());
        $xmlArr = $parser->getBySelector('#Form_Form_DateFormat_yyyy-MM-dd');
        $this->assertEquals('checked', (string) $xmlArr[0]['checked']);
    }

    public function testTimeFormatDefaultCheckedInFormField()
    {
        Config::inst()->update(i18n::class, 'time_format', 'h:mm:ss a');
        $field = $this->createTimeFormatFieldForMember($this->objFromFixture(Member::class, 'noformatmember'));
        /**
 * @skipUpgrade
*/
        $field->setForm(
            new Form(
                new Controller(),
                'Form',
                new FieldList(),
                new FieldList()
            )
        ); // fake form
        $parser = new CSSContentParser($field->Field());
        $xmlArr = $parser->getBySelector('#Form_Form_TimeFormat_h:mm:ss_a');
        $this->assertEquals('checked', (string) $xmlArr[0]['checked']);
    }

    public function testDateFormatChosenIsCheckedInFormField()
    {
        $member = $this->objFromFixture(Member::class, 'noformatmember');
        $member->setField('DateFormat', 'MM/dd/yyyy');
        $field = $this->createDateFormatFieldForMember($member);
        /**
 * @skipUpgrade
*/
        $field->setForm(
            new Form(
                new Controller(),
                'Form',
                new FieldList(),
                new FieldList()
            )
        ); // fake form
        $parser = new CSSContentParser($field->Field());
        $xmlArr = $parser->getBySelector('#Form_Form_DateFormat_MM_dd_yyyy');
        $this->assertEquals('checked', (string) $xmlArr[0]['checked']);
    }

    public function testDateFormatCustomFormatAppearsInCustomInputInField()
    {
        $member = $this->objFromFixture(Member::class, 'noformatmember');
        $member->setField('DateFormat', 'dd MM yy');
        $field = $this->createDateFormatFieldForMember($member);
        /**
 * @skipUpgrade
*/
        $field->setForm(
            new Form(
                new Controller(),
                'Form',
                new FieldList(),
                new FieldList()
            )
        ); // fake form
        $parser = new CSSContentParser($field->Field());
        $xmlInputArr = $parser->getBySelector('.valcustom input');
        $this->assertEquals('checked', (string) $xmlInputArr[0]['checked']);
        $this->assertEquals('dd MM yy', (string) $xmlInputArr[1]['value']);
    }

    public function testDateFormValid()
    {
        $field = new MemberDatetimeOptionsetField('DateFormat', 'DateFormat');
        $validator = new RequiredFields();
        $this->assertTrue($field->validate($validator));
        $field->setValue(
            array(
            'Options' => '__custom__',
            'Custom' => 'dd MM yyyy'
            )
        );
        $this->assertTrue($field->validate($validator));
        $field->setValue(
            array(
            'Options' => '__custom__',
            'Custom' => 'sdfdsfdfd1244'
            )
        );
        $this->assertFalse($field->validate($validator));
    }

    public function testDescriptionTemplate()
    {
        $field = new MemberDatetimeOptionsetField('DateFormat', 'DateFormat');

        $this->assertEmpty($field->getDescription());

        $field->setDescription('Test description');
        $this->assertEquals('Test description', $field->getDescription());

        $field->setDescriptionTemplate(get_class($field).'_description_time');
        $this->assertNotEmpty($field->getDescription());
        $this->assertNotEquals('Test description', $field->getDescription());
    }
}
