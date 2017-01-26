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
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;

class MemberDatetimeOptionsetFieldTest extends SapphireTest
{
    protected static $fixture_file = 'MemberDatetimeOptionsetFieldTest.yml';

    /**
     * @param Member $member
     * @return MemberDatetimeOptionsetField
     */
    protected function createDateFormatFieldForMember($member)
    {
        $defaultDateFormat = $member->getDefaultDateFormat();
        $dateFormatMap = array(
            'yyyy-MM-dd' => DBDatetime::now()->Format('yyyy-MM-dd'),
            'yyyy/MM/dd' => DBDatetime::now()->Format('yyyy/MM/dd'),
            'MM/dd/yyyy' => DBDatetime::now()->Format('MM/dd/yyyy'),
            'dd/MM/yyyy' => DBDatetime::now()->Format('dd/MM/yyyy'),
        );
        $dateFormatMap[$defaultDateFormat] = DBDatetime::now()->Format($defaultDateFormat) . ' (default)';
        $field = new MemberDatetimeOptionsetField(
            'DateFormat',
            'Date format',
            $dateFormatMap
        );
        $field->setValue($member->getDateFormat());
        return $field;
    }

    /**
     * @param Member $member
     * @return MemberDatetimeOptionsetField
     */
    protected function createTimeFormatFieldForMember($member)
    {
        $defaultTimeFormat = $member->getDefaultTimeFormat();
        $timeFormatMap = array(
            'h:mm a' => DBDatetime::now()->Format('h:mm a'),
            'H:mm' => DBDatetime::now()->Format('H:mm'),
        );
        $timeFormatMap[$defaultTimeFormat] = DBDatetime::now()->Format($defaultTimeFormat) . ' (default)';
        $field = new MemberDatetimeOptionsetField(
            'TimeFormat',
            'Time format',
            $timeFormatMap
        );
        $field->setValue($member->getTimeFormat());
        return $field;
    }

    public function testDateFormatDefaultCheckedInFormField()
    {
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'noformatmember');
        $field = $this->createDateFormatFieldForMember($member);
        /** @skipUpgrade */
        $field->setForm(
            new Form(
                new Controller(),
                'Form',
                new FieldList(),
                new FieldList()
            )
        ); // fake form
        // `MMM d, y` is default format for default locale (en_US)
        $parser = new CSSContentParser($field->Field());
        $xmlArr = $parser->getBySelector('#Form_Form_DateFormat_MMM_d_y');
        $this->assertEquals('checked', (string) $xmlArr[0]['checked']);
    }

    public function testTimeFormatDefaultCheckedInFormField()
    {
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'noformatmember');
        $field = $this->createTimeFormatFieldForMember($member);
        /** @skipUpgrade */
        $field->setForm(
            new Form(
                new Controller(),
                'Form',
                new FieldList(),
                new FieldList()
            )
        ); // fake form
        // `h:mm:ss a` is the default for en_US locale
        $parser = new CSSContentParser($field->Field());
        $xmlArr = $parser->getBySelector('#Form_Form_TimeFormat_h:mm:ss_a');
        $this->assertEquals('checked', (string) $xmlArr[0]['checked']);
    }

    public function testDateFormatChosenIsCheckedInFormField()
    {
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'noformatmember');
        $member->setField('DateFormat', 'MM/dd/yyyy');
        $field = $this->createDateFormatFieldForMember($member);
        /** @skipUpgrade */
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
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'noformatmember');
        $member->setField('DateFormat', 'dd MM yy');
        $field = $this->createDateFormatFieldForMember($member);
        /** @skipUpgrade */
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
        $field->setSubmittedValue([
            'Options' => '__custom__',
            'Custom' => 'dd MM yyyy'
        ]);
        $this->assertTrue($field->validate($validator));
        $field->setSubmittedValue([
            'Options' => '__custom__',
            'Custom' => 'sdfdsfdfd1244'
        ]);
        // @todo - Be less forgiving of invalid CLDR date format strings
        $this->assertTrue($field->validate($validator));
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
